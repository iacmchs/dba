<?php

declare(strict_types=1);

namespace App\Anonymization;

/**
 * Db data anonymizer setter interface.
 */
interface AnonymizerSetterInterface
{
    /**
     * Set anonymizer for data extractor.
     *
     * @param \App\Anonymization\AnonymizerInterface $anonymizer
     *   Data anonymizer.
     *
     * @return void
     */
    public function setAnonymizer(AnonymizerInterface $anonymizer): void;
}
