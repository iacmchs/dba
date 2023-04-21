<?php

declare(strict_types=1);

namespace App\Service\Anonymization;

/**
 * Db data anonymizer setter interface.
 */
interface AnonymizerSetterInterface
{
    /**
     * Set anonymizer for data extractor.
     *
     * @param \App\Service\Anonymization\AnonymizerInterface $anonymizer
     *   Data anonymizer.
     *
     * @return void
     */
    public function setAnonymizer(AnonymizerInterface $anonymizer): void;
}
