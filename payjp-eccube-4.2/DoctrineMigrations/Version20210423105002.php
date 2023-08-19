<?php declare(strict_types=1);

namespace Plugin\PayJp\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210423105002 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        // this up() migration is auto-generated, please modify it to your needs
        // $Table = $schema->getTable('plg_payjp_customer');
        // if(!$Table){
        //     $this->addSql("CREATE TABLE plg_payjp_customer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, customer_id INT UNSIGNED DEFAULT NULL, payjp_customer_id VARCHAR(255) NOT NULL, is_save_card_on INT DEFAULT 0, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_DB0CA91F9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB;");
        // }
        $Table = $schema->getTable('plg_pay_jp_config');
        if (!$Table->hasColumn('is_auth_and_capture_on')) {
            $this->addSql("ALTER TABLE plg_pay_jp_config ADD is_auth_and_capture_on INT DEFAULT 0;"); 
        }
        if (!$Table->hasColumn('payjp_fees_percent')) {
            $this->addSql("ALTER TABLE plg_pay_jp_config ADD payjp_fees_percent NUMERIC(12, 2) UNSIGNED DEFAULT '0' NOT NULL;"); 
        }
        $Table = $schema->getTable('plg_pay_jp_order');
        if (!$Table->hasColumn('pay_jp_customer_id_for_guest_checkout')) {
            $this->addSql("ALTER TABLE plg_pay_jp_order ADD pay_jp_customer_id_for_guest_checkout VARCHAR(255) DEFAULT NULL;"); 
        }
        if (!$Table->hasColumn('is_charge_captured')) {
            $this->addSql("ALTER TABLE plg_pay_jp_order ADD is_charge_captured INT DEFAULT 0;"); 
        }
        if (!$Table->hasColumn('is_charge_refunded')) {
            $this->addSql("ALTER TABLE plg_pay_jp_order ADD is_charge_refunded INT DEFAULT 0;"); 
        }
        if (!$Table->hasColumn('selected_refund_option')) {
            $this->addSql("ALTER TABLE plg_pay_jp_order ADD selected_refund_option INT UNSIGNED DEFAULT 0;"); 
        }
        if (!$Table->hasColumn('refunded_amount')) {
            $this->addSql("ALTER TABLE plg_pay_jp_order ADD refunded_amount NUMERIC(12, 2) UNSIGNED DEFAULT '0' NOT NULL;"); 
        }
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql("CREATE TABLE plg_payjp_customer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, customer_id INT UNSIGNED DEFAULT NULL, payjp_customer_id VARCHAR(255) NOT NULL, is_save_card_on INT DEFAULT 0, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_DB0CA91F9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB;");
        // $this->addSql("ALTER TABLE plg_payjp_customer ADD CONSTRAINT FK_DB0CA91F9395C3F3 FOREIGN KEY (customer_id) REFERENCES dtb_customer (id);");
        // $this->addSql("ALTER TABLE plg_pay_jp_config ADD is_auth_and_capture_on INT DEFAULT 0, ADD payjp_fees_percent NUMERIC(12, 2) UNSIGNED DEFAULT '0' NOT NULL;");
        // $this->addSql("ALTER TABLE plg_pay_jp_order ADD pay_jp_customer_id_for_guest_checkout VARCHAR(255) DEFAULT NULL, ADD is_charge_captured INT DEFAULT 0, ADD is_charge_refunded INT DEFAULT 0, ADD selected_refund_option INT UNSIGNED DEFAULT 0, ADD refunded_amount NUMERIC(12, 2) UNSIGNED DEFAULT '0' NOT NULL;");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
