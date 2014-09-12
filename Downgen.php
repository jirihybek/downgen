<?php
/*
 * DownGEN - Markdown on-air documentation generator
 *
 * @author Jiri Hybek <jiri@hybek.cz>
 * See LICENSE.md distributed with this source code for licensing info.
 */

require_once(dirname(__FILE__) . "/Parsedown.php");
require_once(dirname(__FILE__) . "/ParsedownExtra.php");

/*
 * DOWNGEN CLASS
 */
class DownGen {

	public $title = "DownGEN Generator";
	public $description = "Markdown documentation";
	public $cssUrl = "_downgen/downgen.css";
	public $indexName = "README";

	public $excludePattern = "[_\.]";

	protected $pageDir = null;
	protected $pageList = null;

	/*
	 * Downgen constructor
	 *
	 * @param String $pageDir
	 * @throw DowngenException
	 * @void
	 */
	public function __construct($pageDir){

		if(!file_exists($pageDir)) throw new DowngenException("Page directory '{$pageDir}' not found.");

		$this->pageDir = $pageDir;
		$this->pageList = $this->getPageList($pageDir);

	}

	/*
	 * Scans pages dir and return file list without extension
	 *
	 * @param String $baseDir
	 * @param String $prefix
	 * @return Array
	 */
	protected function getPageList($baseDir, $prefix = ""){

		$dirList = array();
		$pageList = array();
		$resultList = array();

		$files = scandir($baseDir);

		foreach($files as $filename){

			if(preg_match("/^(\.\.|" . $this->excludePattern .")/", $filename)) continue;

			if(is_dir("{$baseDir}/{$filename}")){

				$dirList[$filename] = $this->getPageList("{$baseDir}/{$filename}", $filename . "/");

			} elseif(substr($filename, -3, 3) == ".md") {

				$pageList[$prefix . basename($filename, ".md")] = basename($filename, ".md");

			}

		}

		ksort($dirList);
		ksort($pageList);

		foreach($dirList as $k => $v)  $resultList[$k] = $v;
		foreach($pageList as $k => $v) $resultList[$k] = $v;

		return $resultList;

	}

	/*
	 * Renders navigation tree
	 *
	 * @param Array $pages
	 * @param String $current
	 * @void
	 */
	protected function renderNavFolder($pages = array(), $current = null){
?>
<ul>
<?php foreach($pages as $name => $item): ?>
	<?php if(is_array($item)): ?> 
	<li class="dir">
		<span class="title"><?php echo preg_replace("/([0-9]+__|[-_])/", " ", $name); ?></span>
<?php $this->renderNavFolder($item, $current); ?>
	</li>
	<?php else: ?> 
	<li class="page"><a href="?page=<?php echo urlencode($name); ?>" class="<?php if($name == $current): ?>current<?php endif; ?>"><?php echo preg_replace("/([0-9]+__|[-_])/", " ", $item); ?></a></li>
	<?php endif; ?>
<?php endforeach; ?>
</ul>
<?php
	}

	/*
	 * Renders navigation tree as markdown
	 *
	 * @param Array $pages
	 * @param Integer $level
	 * @void
	 */
	protected function renderNavDown($pages = array(), $level = 2){

		foreach($pages as $name => $item){

			if(is_array($item)){

				for($l = 0; $l < $level; $l++) echo "#";
				echo preg_replace("/([0-9]+__|[-_])/", " ", $name) . "\n";

				echo $this->renderNavDown($item, $level + 1) . "  \n";

			} else {

				echo '[' . preg_replace("/([0-9]+__|[-_])/", " ", $item) . '](./' . urlencode($name) . '.md)' . "\n";

			}

		}

	}

	/*
	 * Render page
	 *
	 * @void
	 */
	public function render(){

		//Get page
		$pageName = ( isset($_GET['page']) ? $_GET['page'] : $this->indexName );

?>
<!doctype html>
	<head>
		<title><?php echo $this->title; ?></title>
		<link rel="stylesheet" href="<?php echo $this->cssUrl; ?>" />
		<script type="text/javascript">

			function toggleNavDown(){
				document.getElementById('nav-down').classList.toggle('opened');
			}

		</script>
	</head>
	<body>

		<div id="sidebar">
			<p id="doc-title"><a href="?page=<?php echo $this->indexName; ?>" title="Go to index page"><?php echo $this->title; ?></a></p>
			<p id="doc-description"><?php echo $this->description; ?></p>
			<nav>
<?php $this->renderNavFolder($this->pageList, $pageName); ?>
			</nav>
			<textarea id="nav-down" wrap="off"><?php $this->renderNavDown($this->pageList); ?></textarea>
			<div id="toolbar">
				<a class="icon icon-nav" href="javascript:toggleNavDown()" title="Show navigation as markdown source">Show navigation as markdown</a>
			</div>
		</div>

		<div id="content" class="markdown-body">
<?php

	$pageFilename = realpath("{$this->pageDir}/{$pageName}.md");

	//Relative path protection
	if(substr($pageFilename, 0, strlen($this->pageDir)) != $this->pageDir) $pageFilename = null;

	//Is relative with upper dir? Redirect to right url
	if(preg_match("/(\.\/|\.\.\/|\\\)/", $pageName))
		header("Location: ?page=" . str_replace("\\", "/", substr($pageFilename, strlen($this->pageDir) + 1, strlen($pageFilename) - strlen($this->pageDir) - 4)));

	if(file_exists($pageFilename)):

		$pageContents = file_get_contents("{$this->pageDir}/{$pageName}.md");

		$pageContents = preg_replace("/\[(.*)\]\(\.\/(.*)\.(md|MD)\)/", '[${1}](?page=' . dirname($pageName) . '/${2})' , $pageContents);

		$parser = new ParsedownExtra();
		echo $parser->text($pageContents);

	else:
?>
			<h1>Page not found</h1>
			<p>The page you are requesting was not found in this documentation.</p>
<?php endif; ?>
		</div>

	</body>
</html>
<?php

	}

}

/*
 * EXCEPTIONS
 */
class DowngenException extends Exception {}