<?php declare(strict_types=1);

namespace Murdej\TsLinkPhp\Bridges\Symfony;

use App\Services\ItemFinder;
use Murdej\TsLinkPhp\Bridges\Nette\TLApplication;
use Murdej\TsLinkPhp\TsCodeGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'gen:ts-link',
    description: 'Generate ts file from registered *TL classes.',
)]

class GenTlCommand extends Command {
    protected function configure()
    {
        // $this->addArgument('part', InputArgument::OPTIONAL, 'empty - auto, cache, send');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Generating {$this->tlFilePath}...");
        $tcg = new TsCodeGenerator();
        $tcg->baseClassName = 'MyBaseTL';
        $tcg->baseClassRequire = '../MyBaseTL';
        foreach ($this->generatorOpts as $k => $v) {
            $tcg->$k = $v;
        }
        foreach ($this->tlApplication->getRegisterClasses() as $cn)
        {
            $output->writeln("    Add $cn");
            $tcg->add($cn, $this->tlApplication->getLinkForClass($cn));
        };
        $code = $tcg->generateCode();
        $output->writeln("  " . (file_put_contents($this->tlFilePath, $code) ? "Ok" : "Failed"));

        return Command::SUCCESS;
    }

    public function __construct(
        protected string $tlFilePath,
        protected TLApplication $tlApplication,
        public array $generatorOpts = [],
    )
    {
        parent::__construct();
    }
}