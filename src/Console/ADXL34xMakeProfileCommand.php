<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Console;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xCatalogIc;
use Fabricate\Console\Command;
use GeneralPurposeIO\Circuits\CircuitRegistry;
use GeneralPurposeIO\Circuits\Console\Concerns\ScaffoldsCircuitProfiles;
use GeneralPurposeIO\Circuits\Support\CircuitAttributeInspector;
use GeneralPurposeIO\Contracts\Circuits\CircuitException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'adxl34x:make-profile')]
class ADXL34xMakeProfileCommand extends Command
{
    use ScaffoldsCircuitProfiles;

    protected ?string $signature = 'adxl34x:make-profile
                    {ic? : One of adxl343, adxl345}
                    {name? : Profile key to write into config/circuits.php}
                    {--protocol= : Protocol option label or factory name when non-interactive}';

    protected string $description = 'Scaffold a circuits.php profile for an ADXL34x accelerometer';

    public function handle(CircuitRegistry $registry): int
    {
        $available = array_values(array_filter(
            ADXL34xCatalogIc::slugs(),
            static fn (string $ic): bool => isset($registry->listCircuits()[$ic]),
        ));

        if ($available === []) {
            $this->components->error('No ADXL34x ICs are registered.');

            return self::FAILURE;
        }

        $ic = $this->argument('ic');
        if (is_null($ic) || $ic === '') {
            $ic = $this->choice('Which ADXL34x IC?', $available);
        }

        $ic = (string) $ic;

        if (is_null(ADXL34xCatalogIc::tryFrom($ic))) {
            $this->components->error("IC [{$ic}] is not an ADXL34x sensor.");

            return self::FAILURE;
        }

        try {
            $options = CircuitAttributeInspector::protocolOptions($registry->resolveClass($ic));
        } catch (CircuitException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $selected = $this->resolveProtocolOption($options);
        if (is_null($selected)) {
            return self::FAILURE;
        }

        $name = $this->argument('name');
        if (is_null($name) || $name === '') {
            $name = $this->ask('Profile name', $ic);
        }

        return $this->writePromptedProfile($ic, (string) $name, $selected);
    }
}
