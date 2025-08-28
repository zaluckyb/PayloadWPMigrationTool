import { Command } from 'commander';
import { discover } from './commands/discover.js';

const program = new Command();

program
  .name('payload-wp-migrate')
  .description('Migration orchestrator for WordPress → Payload CMS')
  .version('0.1.0');

program
  .command('discover')
  .description('Discover WordPress types and produce a mapping draft')
  .action(async () => {
    await discover();
  });

program.parseAsync(process.argv);
