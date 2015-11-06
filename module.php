<?php
// Classes and libraries for module system
//
// webtrees: Web based Family History software
// Copyright (C) 2015 £ukasz Wileñski.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
namespace Wooc\WebtreesAddon\WoocServerUsageModule;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\PageController;
use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use PDO;

class WoocServerUsageModule extends AbstractModule implements ModuleConfigInterface {

	public function __construct() {
		parent::__construct('wooc_server_usage');
	}

	// Extend Module
	public function getTitle() {
		return I18N::translate('Wooc Server Usage');
	}

	// Extend Module
	public function getDescription() {
		return I18N::translate('Allows you to easily check the usage of disk space and database size on the server.');
	}

	// Extend Module
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'admin_config':
			$this->config();
			break;
		default:
			http_response_code(404);
			break;
		}
	}

	// Implement Module_Config
	public function getConfigLink() {
		return 'module.php?mod='.$this->getName().'&amp;mod_action=admin_config';
	}

	private function config() {
		global $WT_TREE;
		$controller = new PageController;
		$controller
			->restrictAccess(Auth::isAdmin())
			->setPageTitle(I18N::translate('Server usage'))
			->pageHeader();
		$db_size = $this->dbSize();
		$media_size = $this->directorySize(WT_DATA_DIR . $WT_TREE->getPreference('MEDIA_DIRECTORY'));
		$root_size = $this->directorySize(WT_ROOT);
		?>
		<ol class="breadcrumb small">
			<li><a href="admin.php"><?php echo I18N::translate('Control panel'); ?></a></li>
			<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration'); ?></a></li>
			<li class="active"><?php echo $controller->getPageTitle(); ?></li>
		</ol>
		<div class="row">
			<div class="col-sm-8 col-xs-12">
				<h3><?php echo I18N::translate('All trees'); ?></h3>
				<ul class="server_stats">
					<li>
						<?php echo I18N::translate('%s Individuals', $this->siteIndividuals()); ?>
					</li>
					<li>
						<?php echo I18N::translate('%s Media objects', $this->siteMedia()); ?>
					</li>
					<li>
						<?php echo I18N::translate('Your database size is currently %s MB', I18N::number($db_size)); ?>
					</li>
					<li>
						<?php echo I18N::translate('Your media files are currently using %s MB', I18N::number($media_size)); ?>
					</li>
					<li>
						<?php echo I18N::translate('Your files excluding media items are currently using %s MB', I18N::number($root_size - $media_size)); ?>
					</li>
					<li>
						<?php echo I18N::translate('Total server space used is therefore %s MB', I18N::number($db_size + $root_size)); ?>
					</li>
				</ul>
			</div>
		</div>
		<?php
	}

	private function siteIndividuals() {
		$count = Database::prepare("SELECT SQL_CACHE COUNT(*) FROM `##individuals`")
			->execute()
			->fetchOne();
		return	I18N::number($count);
	}

	private function siteMedia() {
		$count = Database::prepare("SELECT SQL_CACHE COUNT(*) FROM `##media`")
			->execute()
			->fetchOne();
		return I18N::number($count);
	}

	private function dbSize() {
		$sql = 'SHOW TABLE STATUS';
		$size = 0;
		$rows = Database::prepare($sql)->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) {
			$size += $row['data_length'] + $row['index_length'];
		}
		return $size/(1024*1024);
	}
	
	private function directorySize($directory) {
		$total_size = 0;
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file){
			$total_size += $file->getSize();
		}
		return $total_size/(1024*1024);
	}
}

return new WoocServerUsageModule;