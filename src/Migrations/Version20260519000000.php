<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519000000 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Add interactions column to form_field.';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('form_field');
        $table->addColumn('interactions', 'json', ['notnull' => false]);
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $schema->getTable('form_field')->dropColumn('interactions');
    }
}
