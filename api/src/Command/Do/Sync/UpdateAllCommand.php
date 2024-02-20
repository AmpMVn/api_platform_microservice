<?php

namespace App\Command\Do\Sync;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'do:sync',
    description: 'Do sync',
)]
class UpdateAllCommand extends Command
{

    public function __construct()
    {

        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addArgument('client-uuid', InputArgument::OPTIONAL, 'Provide client uuid for single client')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $start = microtime(true);

        $io = new SymfonyStyle($input, $output);
        $io->title("START COMPLETE (MS ARTICLE)");


        # SETTINGS
        $settingsPricegroups = $this->getApplication()->find('do:sync:rs:settings:pricegroups');
        $settingsPricegroups->run(new ArrayInput([]), $output);

        $settingsLocation = $this->getApplication()->find('do:sync:rs:settings:locations');
        $settingsLocation->run(new ArrayInput([]), $output);

        $settingsStorages = $this->getApplication()->find('do:sync:rs:settings:storages');
        $settingsStorages->run(new ArrayInput([]), $output);

        # ARTICLES
        $articles = $this->getApplication()->find('do:sync:rs:articles');
        $articles->run(new ArrayInput([]), $output);

        $articleExtras = $this->getApplication()->find('do:sync:rs:articles:extras');
        $articleExtras->run(new ArrayInput([]), $output);

        $articleImages = $this->getApplication()->find('do:sync:rs:articles:images');
        $articleImages->run(new ArrayInput([]), $output);

        $articleStocks = $this->getApplication()->find('do:sync:rs:articles:stocks');
        $articleStocks->run(new ArrayInput([]), $output);

        $articleAttributes = $this->getApplication()->find('do:sync:rs:articles:attributes');
        $articleAttributes->run(new ArrayInput([]), $output);

        $articlePriceRates = $this->getApplication()->find('do:sync:rs:articles:pricerates');
        $articlePriceRates->run(new ArrayInput([]), $output);

        $articleOnlineBookings = $this->getApplication()->find('do:sync:rs:articles:online-bookings');
        $articleOnlineBookings->run(new ArrayInput([]), $output);


        # OBJECTS
//        $objectsEquipment = $this->getApplication()->find('do:sync:rs:objects:equipments');
//        $objectsEquipment->run(new ArrayInput([]), $output);
//
//        $objectExtras = $this->getApplication()->find('do:sync:rs:objects:extras');
//        $objectExtras->run(new ArrayInput([]), $output);
//
//        $objectPricerates = $this->getApplication()->find('do:sync:rs:objects:pricerates');
//        $objectPricerates->run(new ArrayInput([]), $output);
//
//        $objectsOnlineBooking = $this->getApplication()->find('do:sync:rs:objects:online-bookings');
//        $objectsOnlineBooking->run(new ArrayInput([]), $output);

//        $articleFilters = $this->getApplication()->find('do:sync:microservice:online-booking:filters');
//        $articleFilters->run(new ArrayInput([]), $output);




        $runtime = microtime(true) - $start;
        $io->title("\n\nFINISH, RUNTIME: ". gmdate("H:i:s", $runtime) );

        return Command::SUCCESS;
    }

}
