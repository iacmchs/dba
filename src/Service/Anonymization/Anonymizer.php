<?php

declare(strict_types=1);

namespace App\Service\Anonymization;

use App\Configuration\ConfigurationManagerInterface;
use App\Exception\UnknownAnonymizationMethodException;
use Faker\Factory as FakerFactory;
use Faker\Generator;

/**
 * Handles data anonymization for rows from DB tables.
 */
class Anonymizer implements AnonymizerInterface
{

    /**
     * A faker instance to generate random data.
     *
     * @var \Faker\Generator
     */
    private Generator $faker;

    public function __construct(private readonly ConfigurationManagerInterface $configurationManager)
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * @inheritDoc
     */
    public function anonymize(string $tableName, array $row, array $tableAnonymization = []): array
    {
        if (!$tableAnonymization) {
            $tableAnonymization = $this->configurationManager->getTableAnonymization($tableName);
        }

        foreach ($tableAnonymization as $anonymizationRule) {
            if ($this->shouldAnonymizationRuleBeApplied($anonymizationRule, $row)) {
                foreach ($anonymizationRule['fields'] as $fieldName => $anonymization) {
                    $row[$fieldName] = $this->applyAnonymization($anonymization, $row);
                }
            }
        }

        return $row;
    }

    /**
     * Checks if anonymization rule should be applied to the data (table row).
     *
     * @param array $anonymizationRule
     *   Anonymization rule from project config file.
     * @param array $row
     *   Row from db table.
     *
     * @return bool
     *   TRUE if row passed all conditions of a rule, FALSE otherwise.
     */
    private function shouldAnonymizationRuleBeApplied(array $anonymizationRule, array $row): bool
    {
        $res = true;

        foreach ($anonymizationRule['where'] ?? [] as $fieldName => $condition) {
            if (!is_array($condition)) {
                $condition = [$condition, '='];
            }

            $res = match ($condition[1]) {
                '!=' => (string) $row[$fieldName] !== (string) $condition[0],
                '>' => $row[$fieldName] > $condition[0],
                '>=' => $row[$fieldName] >= $condition[0],
                '<' => $row[$fieldName] < $condition[0],
                '<=' => $row[$fieldName] <= $condition[0],
                'regex' => (bool) preg_match($condition[0], (string) $row[$fieldName]),
                default => (string) $row[$fieldName] === (string) $condition[0],
            };

            if (!$res) {
                break;
            }
        }

        return $res;
    }

    /**
     * Anonymizes a single value (field) of a row.
     *
     * @param mixed $anonymization
     *   Field anonymization operations.
     * @param array $row
     *   Row from db table.
     *
     * @return mixed
     *   Anonymized value.
     *
     * @throws \App\Exception\UnknownAnonymizationMethodException
     */
    private function applyAnonymization(mixed $anonymization, array $row): mixed
    {
        // If anonymized value is a simple value then just return it.
        if (!is_array($anonymization)) {
            // If value is like `%fieldname` then we need to copy the value
            // of respective field from $row.
            if (str_starts_with((string) $anonymization, '%')) {
                $anonymization = $row[substr($anonymization, 1)];
            }

            return is_null($anonymization) || strtolower($anonymization) === 'null'
                ? null
                : $anonymization;
        }

        // If we have a faker method then get the method name.
        if (str_contains($anonymization['method'], '::')) {
            [$anonymization['method'], $subMethod] = explode('::', $anonymization['method']);
        }

        // Preprocess arguments.
        foreach ($anonymization['args'] ?? [] as $key => $arg) {
            // If argument contains a method.
            if (is_array($arg)) {
                $anonymization['args'][$key] = $this->applyAnonymization($arg, $row);
            // If argument is like `%fieldname` then we need to copy the value
            // of respective field from $row to this argument.
            } elseif (str_starts_with((string) $arg, '%')) {
                $anonymization['args'][$key] = $row[substr($arg, 1)];
            }
        }

        // Call a method to anonymize value.
        switch (strtolower($anonymization['method'])) {
            case 'faker':
                $value = call_user_func_array(array($this->faker, $subMethod), $anonymization['args'] ?? []);
                break;

            case 'concat':
                $value = implode('', $anonymization['args']);
                break;

            default:
                throw new UnknownAnonymizationMethodException($anonymization['method']);
        }

        return $value ?? null;
    }
}
