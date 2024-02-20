<?php

namespace App\Command\Do\Sync;

use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'do:sync:every:15:minutes',
    description: 'Do sync',
)]
class Every15MinutesCommand extends Command
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
        $io->title("START EVERY 15 MINUTES (MS ARTICLE)");

        if ($input->getArgument('client-uuid')) {
            $io->text("SINGLE FETCH OF: " . $input->getArgument('client-uuid'));
        }

        # BOOKINGS
        $run = $this->getApplication()->find('do:sync:rs:objects:bookings');
        $run->run(new ArrayInput(['client-uuid' => $input->getArgument('client-uuid')]), $output);

//        $run = $this->getApplication()->find('do:sync:rs:articles:bookings');
//        $run->run(new ArrayInput(['client-uuid' => $input->getArgument('client-uuid')]), $output);

        # MAIL NOTIFICATION
        $mailer = new PHPMailer();
        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->Host = "smtp.office365.com";
        $mailer->Username = "mk@0x0-marketing.de";
        $mailer->Password = "!mokiROCKT2020!";
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = 587;
        $mailer->setFrom('mk@0x0-marketing.de', '0x0 Marketing GmbH - Development');
        $mailer->addAddress('mk@0x0-marketing.de', '0x0 Marketing GmbH - Development');
        $mailer->addReplyTo('mk@0x0-marketing.de', '0x0 Marketing GmbH - Development');
        $mailer->isHTML(true);
        $mailer->Subject = 'Sync commands notification from rentsoft_ms_article_every_15_minutes';
        $mailer->Body = 'Dear 0x0 Development Team,<br><br>
                         we want inform you about a automatic running script in our environment. Please check the following content:<br><br>
                         <b>Date:</b> ' . date("d.m.Y H:i:s") . '<br>
                         <b>Project:</b> rentsoft_ms_article_every_15_minutes<br>
                         <b>Action:</b> Sync command<br><br>
                         Your 0x0 Marketing GmbH Development Team<br><br><small>Auto generated email, do not answer!</small>';
        try {
            $mailer->send();
            echo "SEND";
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }

        $runtime = microtime(true) - $start;
        $io->title("\n\nFINISH, RUNTIME: ". gmdate("H:i:s", $runtime) );

        return Command::SUCCESS;
    }

}
