<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Description of LegalityDecklistsLimitCommand
 *
 * @author Alsciende <alsciende@icloud.com>
 */
class LegalityDecklistsLimitCommand extends ContainerAwareCommand
{
    
    protected function configure ()
    {
        $this
                ->setName('nrdb:legality:decklists-limit')
                ->setDescription('Compute decklist legality regarding card limits')
        ;
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        /* @var $entityManager \Doctrine\ORM\EntityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $countDql = "SELECT COUNT(DISTINCT d) FROM AppBundle:Decklist d"
                . " JOIN AppBundle:Decklistslot s WITH s.decklist=d"
                . " JOIN AppBundle:Card c WITH s.card=c"
                . " WHERE s.quantity>c.deckLimit"
                . " AND d.isLegal=true";
        $countQuery = $entityManager->createQuery($countDql);
        $count = $countQuery->getSingleResult()[1];
        $output->writeln("<comment>Found $count decklists to change</comment>");

        if (!$count) {
            return;
        }

        $progress = new ProgressBar($output, $count);
        $progress->setRedrawFrequency(10);
        $progress->start();

        $fetchDql = str_replace('COUNT(DISTINCT d)', 'DISTINCT d', $countDql);
        $fetchQuery = $entityManager->createQuery($fetchDql)->setMaxResults(1);
        while ($count--) {
            /* @var $decklist \AppBundle\Entity\Decklist */
            $decklist = $fetchQuery->getSingleResult();
            $decklist->setIsLegal(false);
            $entityManager->flush();
            $entityManager->detach($decklist);
            $progress->advance();
        }

        $progress->finish();
        $output->writeln("<info>Done</info>");
    }

}
