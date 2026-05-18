<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Initial schema for letkode/form-schema bundle.';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $form = $schema->createTable('form');
        $form->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $form->addColumn('uuid', 'guid', ['notnull' => true]);
        $form->addColumn('tag', 'string', ['length' => 100, 'notnull' => true]);
        $form->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $form->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => true]);
        $form->addColumn('default_lang', 'string', ['length' => 5, 'notnull' => true, 'default' => 'es']);
        $form->addColumn('parameters', 'json', ['notnull' => true]);
        $form->addColumn('translations', 'json', ['notnull' => false]);
        $form->addColumn('deleted_at', 'datetime', ['notnull' => false]);
        $form->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $form->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $form->setPrimaryKey(['id']);
        $form->addUniqueIndex(['uuid'], 'uniq_form_uuid');
        $form->addUniqueIndex(['tag'], 'uniq_form_tag');

        $formSection = $schema->createTable('form_section');
        $formSection->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $formSection->addColumn('uuid', 'guid', ['notnull' => true]);
        $formSection->addColumn('form_id', 'bigint', ['notnull' => true]);
        $formSection->addColumn('tag', 'string', ['length' => 100, 'notnull' => true]);
        $formSection->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $formSection->addColumn('description', 'text', ['notnull' => false]);
        $formSection->addColumn('position', 'integer', ['notnull' => true, 'default' => 0]);
        $formSection->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => true]);
        $formSection->addColumn('parameters', 'json', ['notnull' => true]);
        $formSection->addColumn('translations', 'json', ['notnull' => false]);
        $formSection->addColumn('deleted_at', 'datetime', ['notnull' => false]);
        $formSection->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $formSection->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $formSection->setPrimaryKey(['id']);
        $formSection->addUniqueIndex(['uuid'], 'uniq_form_section_uuid');
        $formSection->addUniqueIndex(['form_id', 'tag'], 'uniq_form_section_tag');
        $formSection->addForeignKeyConstraint('form', ['form_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_form_section_form');

        $formGroup = $schema->createTable('form_group');
        $formGroup->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $formGroup->addColumn('uuid', 'guid', ['notnull' => true]);
        $formGroup->addColumn('section_id', 'bigint', ['notnull' => true]);
        $formGroup->addColumn('tag', 'string', ['length' => 100, 'notnull' => true]);
        $formGroup->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $formGroup->addColumn('description', 'text', ['notnull' => false]);
        $formGroup->addColumn('position', 'integer', ['notnull' => true, 'default' => 0]);
        $formGroup->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => true]);
        $formGroup->addColumn('parameters', 'json', ['notnull' => true]);
        $formGroup->addColumn('translations', 'json', ['notnull' => false]);
        $formGroup->addColumn('deleted_at', 'datetime', ['notnull' => false]);
        $formGroup->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $formGroup->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $formGroup->setPrimaryKey(['id']);
        $formGroup->addUniqueIndex(['uuid'], 'uniq_form_group_uuid');
        $formGroup->addUniqueIndex(['section_id', 'tag'], 'uniq_form_group_tag');
        $formGroup->addForeignKeyConstraint('form_section', ['section_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_form_group_section');

        $formField = $schema->createTable('form_field');
        $formField->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $formField->addColumn('uuid', 'guid', ['notnull' => true]);
        $formField->addColumn('group_id', 'bigint', ['notnull' => true]);
        $formField->addColumn('tag', 'string', ['length' => 100, 'notnull' => true]);
        $formField->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $formField->addColumn('description', 'text', ['notnull' => false]);
        $formField->addColumn('type', 'string', ['length' => 50, 'notnull' => true]);
        $formField->addColumn('position', 'integer', ['notnull' => true, 'default' => 0]);
        $formField->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => true]);
        $formField->addColumn('attributes', 'json', ['notnull' => true]);
        $formField->addColumn('parameters', 'json', ['notnull' => true]);
        $formField->addColumn('translations', 'json', ['notnull' => false]);
        $formField->addColumn('deleted_at', 'datetime', ['notnull' => false]);
        $formField->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $formField->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $formField->setPrimaryKey(['id']);
        $formField->addUniqueIndex(['uuid'], 'uniq_form_field_uuid');
        $formField->addUniqueIndex(['group_id', 'tag'], 'uniq_form_field_tag');
        $formField->addForeignKeyConstraint('form_group', ['group_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_form_field_group');

        $formOptionGeneral = $schema->createTable('form_option_general');
        $formOptionGeneral->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $formOptionGeneral->addColumn('uuid', 'guid', ['notnull' => true]);
        $formOptionGeneral->addColumn('tag', 'string', ['length' => 100, 'notnull' => true]);
        $formOptionGeneral->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $formOptionGeneral->addColumn('parameters', 'json', ['notnull' => true]);
        $formOptionGeneral->addColumn('translations', 'json', ['notnull' => false]);
        $formOptionGeneral->addColumn('deleted_at', 'datetime', ['notnull' => false]);
        $formOptionGeneral->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $formOptionGeneral->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $formOptionGeneral->setPrimaryKey(['id']);
        $formOptionGeneral->addUniqueIndex(['uuid'], 'uniq_form_option_general_uuid');
        $formOptionGeneral->addUniqueIndex(['tag'], 'uniq_form_option_general_tag');

        $formOptionGeneralValue = $schema->createTable('form_option_general_value');
        $formOptionGeneralValue->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $formOptionGeneralValue->addColumn('uuid', 'guid', ['notnull' => true]);
        $formOptionGeneralValue->addColumn('group_id', 'bigint', ['notnull' => true]);
        $formOptionGeneralValue->addColumn('tag', 'string', ['length' => 100, 'notnull' => true]);
        $formOptionGeneralValue->addColumn('text', 'string', ['length' => 255, 'notnull' => true]);
        $formOptionGeneralValue->addColumn('description', 'text', ['notnull' => false]);
        $formOptionGeneralValue->addColumn('position', 'integer', ['notnull' => true, 'default' => 0]);
        $formOptionGeneralValue->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => true]);
        $formOptionGeneralValue->addColumn('parameters', 'json', ['notnull' => true]);
        $formOptionGeneralValue->addColumn('translations', 'json', ['notnull' => false]);
        $formOptionGeneralValue->addColumn('deleted_at', 'datetime', ['notnull' => false]);
        $formOptionGeneralValue->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $formOptionGeneralValue->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $formOptionGeneralValue->setPrimaryKey(['id']);
        $formOptionGeneralValue->addUniqueIndex(['uuid'], 'uniq_form_option_general_value_uuid');
        $formOptionGeneralValue->addUniqueIndex(['group_id', 'tag'], 'uniq_form_option_general_value_tag');
        $formOptionGeneralValue->addForeignKeyConstraint('form_option_general', ['group_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_form_option_general_value_group');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $schema->dropTable('form_option_general_value');
        $schema->dropTable('form_option_general');
        $schema->dropTable('form_field');
        $schema->dropTable('form_group');
        $schema->dropTable('form_section');
        $schema->dropTable('form');
    }
}
