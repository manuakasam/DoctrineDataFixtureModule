<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace DoctrineDataFixtureModule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use DoctrineDataFixtureModule\Loader\ServiceLocatorAwareLoader;
use Doctrine\ORM\EntityManager;

/**
 * Command to import Fixtures
 *
 * @license MIT
 * @link    www.doctrine-project.org
 * @author  Martin Shwalbe <martin.shwalbe@gmail.com>
 */
class ImportCommand extends Command
{
    protected $paths;

    /**
     * EntityManager
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     * ServiceLocatorAwareLoader
     * @var DoctrineDataFixtureModule\Loader\ServiceLocatorAwareLoader
     */
    protected $loader;

    /**
     * ORMPurger
     * @var Doctrine\Common\DataFixtures\Purger\ORMPurger
     */
    protected $purger;

    const PURGE_MODE_TRUNCATE = 2;
    
    public function __construct(
        ServiceLocatorAwareLoader $loader,
        ORMPurger $purger,
        EntityManager $em,
        array $paths = array()
    ) {
        $this->loader = $loader;
        $this->purger = $purger;
        $this->em = $em;
        $this->paths = $paths;

        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();

        $this->setName('data-fixture:import')
            ->setDescription('Import Data Fixtures')
            ->setHelp(
<<<EOT
The import command Imports data-fixtures
EOT
            )
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append data to existing data.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Truncate tables before inserting data')
            ->addOption(
                'fixtures',
                null,
                InputOption::VALUE_REQUIRED,
                'Set path to Fixture Class or Directory to be added'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('purge-with-truncate')) {
            $this->purger->setPurgeMode(self::PURGE_MODE_TRUNCATE);
        }

        if ($input->getOption('fixtures') !== null) {
            $this->loader->loadPath($input->getOption('fixtures'));
        } else {
            $this->loader->loadPaths($this->paths);
        }

        $executor = new ORMExecutor($this->em, $this->purger);

        $executor->execute($this->loader->getFixtures(), $input->getOption('append'));
    }
}
