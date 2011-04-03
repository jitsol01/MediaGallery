<?
function saveTags($tags,$my_id)
{
	$keywords = preg_split ("/[\s,]+/", $tags);
	$sql = 'DELETE FROM `tags` WHERE `tags`.`media_id` = '.$my_id.'; ';
	foreach($keywords as $key)
	{
		$sql .= "INSERT INTO `tags` (`text`,`media_id`) VALUES ('".$key."',".$my_id.") LIMIT 1; ";
	}
	return $sql;
}
// converts a title to be url friendly
// returns string
function slug2url($string) {
        $slug = preg_replace("/[^a-zA-Z0-9 ]/", "", $string); // only take alphanumerical characters, but keep the spaces too...
        $slug = str_replace(" ", "-", $slug); // replace spaces by dashes
        return strtolower($slug); // make it lowercase
}
// creates thumbnail named filename_thumb
function createThumb($sourceFilename)
{
	if(empty($sourceFilename)){
		die("No source image");
	}

	if(is_readable($sourceFilename)){
		vendor("phpthumb".DS."phpthumb.class");
		$phpThumb = new phpThumb();

		$phpThumb->src = $sourceFilename;
		$phpThumb->w = 100;
		$phpThumb->h = 75;
		$phpThumb->q = 75;
		$phpThumb->zc = true;
		$phpThumb->config_imagemagick_path = '/usr/bin/convert';
		$phpThumb->config_prefer_imagemagick = true;
		$phpThumb->config_output_format = 'jpg';
		$phpThumb->config_error_die_on_error = true;
		$phpThumb->config_document_root = '';
		$phpThumb->config_temp_directory = APP . 'tmp';
		$phpThumb->config_cache_directory = ROOT.DS.'upload'.DS;
		$phpThumb->config_cache_disable_warning = true;
	   
		$cacheFilename = md5($_SERVER['REQUEST_URI']);
		
		$phpThumb->cache_filename = $sourceFilename.'_thumb';

		if ($phpThumb->GenerateThumbnail()) {
			$phpThumb->RenderToFile($phpThumb->cache_filename);
		} else {
			die('Failed: '.$phpThumb->error);
		}
	}
}
function http_modified($last_modified,$identifier)
{
	$etag = '"'.md5($last_modified.$identifier).'"';
	$client_etag = $_SERVER['HTTP_IF_NONE_MATCH'] ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false;
	$client_last_modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ? trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
	$client_last_modified_timestamp = strtotime($client_last_modified);
	$last_modified_timestamp = strtotime($last_modified);

	if(($client_last_modified && $client_etag) ? (($client_last_modified_timestamp == $last_modified_timestamp) && ($client_etag == $etag)) : (($client_last_modified_timestamp == $last_modified_timestamp) || ($client_etag == $etag)))
	{
		header('Not Modified',true,304);
		exit();
	}
	else
	{
		header('Last-Modified:'.$last_modified);
		header('ETag:'.$etag);
	}
}
class MediaController extends AppController
{
	function feed()
	{
		$results = $this->Media->findAll(null,array('id','title','slug','created','description'),'created DESC',30);
		$this->set('results',$results);
		$this->layout = null;
	}
	/* Calculates All Time view stats */
	function crontab_daily() 
	{
		$time_start = microtime(true);
		// Check the action is being invoked by the cron dispatcher
	//	if (!defined('CRON_DISPATCHER')) { $this->redirect('/'); exit(); }
		
		$this->layout = null; // turn off the layout

		
		// update all time views
		$sql = 'UPDATE media SET media.views = (SELECT count FROM (SELECT media_id,count(*) AS count FROM views GROUP BY media_id ) AS x WHERE media.id=media_id);';
		// week views
		$sql = 'UPDATE media SET media.views_week = (SELECT count FROM (SELECT media_id,count(*) AS count FROM views WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY media_id) AS x WHERE media.id=media_id);';
		$this->Media->query($sql);
		$time_end = microtime(true);
		$time = $time_end - $time_start;

		echo "Executed in $time seconds\n";
		exit;
	}
	function crontab() 
	{
		$time_start = microtime(true);
		// Check the action is being invoked by the cron dispatcher
	//	if (!defined('CRON_DISPATCHER')) { $this->redirect('/'); exit(); }
		
		$this->layout = null; // turn off the layout
		
		// do something here
		/* lets run every 5 min */
		
		// update ratings
		$sql = 'UPDATE media SET media.rating = (SELECT avg(value) FROM (SELECT * FROM ratings) AS value WHERE media.id=media_id);';
		$this->Media->query($sql);
		// update views for today only
		$sql = 'UPDATE media SET media.views_today = (SELECT count FROM (SELECT media_id,count(*) AS count FROM views WHERE date >= CURDATE() GROUP BY media_id) AS x WHERE media.id=media_id);';
		$this->Media->query($sql);
		$time_end = microtime(true);
		$time = $time_end - $time_start;

		echo "Executed in $time seconds\n";
		exit;
	}
	function archive($year=0,$month=0,$day=0)
	{
		$year = (int) $year;
		$month = (int) $month;
		$day = (int) $day;
		if($month > 12 || $month < 1) $month = 0;
		if($day > 31 || $day < 1) $day = 0;
		if($year < 0 || $year > date("Y")) $year = 0;
		if($year == 0 || $month == 0 || $day == 0)
		{
			$this->cakeError('error404',array($this->params['url']));
		}
		
		// print our date 'YEAR-MM-DD'
		$time = mktime(0,0,0,$month,$day,$year);
		$date = date("Y-m-d",$time);
		$dtitle = date("F j, Y",$time);
		// make the sql statement
		$sql = "SELECT * FROM `media` AS Media WHERE DATE(created) = '$date' ORDER BY created DESC;";

		$results = $this->Media->query($sql);
		if(!$results) $this->flash('No results exists for that date','/');
		
		$this->set('results', $results);
		$this->set('title', 'Archive for '.$dtitle);
		$this->set('search', $dtitle);
		
		$this->render('find');
	}
	// value then media_id
	// doesnt make sense but its how cake works
	function rate($val,$mid)
	{
		$id = (int) $mid;
		$value = (int) $val;
		if($id == 0) exit;
		if($value>10) $value = 10;
		elseif($value<0) $value = 0;
		$sql = 'REPLACE INTO `ratings` (`ip`,`media_id`,`value`) VALUES ('.ip2long($_SERVER['REMOTE_ADDR']).','.$id.','.$value.');';
		echo $this->Media->query($sql);
		exit;
	}
	function find()
	{
		$sql = "SELECT * FROM `media` AS Media WHERE MATCH (title, description) AGAINST ('".mysql_real_escape_string($this->params['url']['f'])."');";
		$results = $this->Media->query($sql);
		$this->set('results', $results);
		$this->set('title', $this->params['url']['f']);
		$this->set('search', $this->params['url']['f']);
		//print_r($results);
	}
	function popular()
	{
		$results = $this->Media->findAll(null,array('id','slug','title'),'rating DESC',100);
		$this->set('results', $results);
		$this->set('title', 'Top 100');
		$this->set('search', 'Highest Rated');
		$this->render('find');
	}
	function viewed_today()
	{
		$results = $this->Media->findAll(null,array('id','slug','title'),'views_today DESC',100);
		$this->set('results', $results);
		$this->set('title', 'Most Popular Today');
		$this->set('search', 'Most Viewed Today');
		$this->render('find');

	}
	function viewed_week()
	{
		$results = $this->Media->findAll(null,array('id','slug','title'),'views_week DESC',100);
		$this->set('results', $results);
		$this->set('title', 'Most Popular this Week');
		$this->set('search', 'Most Viewed this Week');
		$this->render('find');

	}
	function viewed_all()
	{
		$results = $this->Media->findAll(null,array('id','slug','title'),'views DESC',100);
		$this->set('results', $results);
		$this->set('title', 'Most Popular');
		$this->set('search', 'Most Viewed');
		$this->render('find');

	}
	function mostviewed()
	{
		$alltime = $this->Media->findAll(null,array('id','slug','title'),'views DESC',20);
		$week = $this->Media->findAll(null,array('id','slug','title'),'views_week DESC',20);
		$today = $this->Media->findAll(null,array('id','slug','title'),'views_today DESC',20);
		$this->set('alltime', $alltime);
		$this->set('week', $week);
		$this->set('today', $today);
		$this->set('title', 'Most Popular');

	}
	function slug($n='')
	{
		$media = $this->Media->findBySlug($n);
		if(!$media)
		{
			// $n not found
			$this->cakeError('error404', array($this->params['url']));
			exit;
		}
		
		$this->set('media',$media['Media']);
		/* set the title, description and imageid for url to be
		 / facebook share compatiable
		 / rendered in the header of 'image' layout
		 */
		$this->set('title', $media['Media']['title']);
		$this->set('description', $media['Media']['description']);
		$this->set('imageid', $media['Media']['id']);
		
		$prev = $this->Media->find('WHERE Media.id < '.$media['Media']['id'],array('slug','title','id'),'Media.id DESC','LIMIT 1');
		$next = $this->Media->find('WHERE Media.id > '.$media['Media']['id'],array('slug','title','id'),'Media.id ASC','LIMIT 1');
		if($prev) $this->set('prev',$prev['Media']);
		if($next) $this->set('next',$next['Media']);

		
		$this->Media->query('REPLACE INTO `views` ( `ip` , `media_id` ) VALUES ('.ip2long($_SERVER['REMOTE_ADDR']).', '.$media['Media']['id'].');');
		$this->layout ='image';
		$this->render('view');
	}
	function random()
	{
		list(list($result)) = $this->Media->query('SELECT count(*) as count FROM `media`');
		$random = rand(1,$result['count']);
		if(!($result = $this->Media->query('SELECT slug FROM `media` WHERE id >= '.$random.' LIMIT 1')))
			$result = $this->Media->query('SELECT slug FROM `media` WHERE id < '.$random.' LIMIT 1');
		$this->redirect('/s/'.$result[0]['media']['slug'].'/');
		exit;
	}
	function create()
	{
	$this->params['data']['Media'] = array();
	$this->params['data']['Media']['url'] = $this->params['url']['u'];
	//$this->params['data']['Media']['title'] = $this->params['url']['t'];
	$this->params['data']['Media']['title'] = 'Change me';
	$this->params['data']['Media']['file'] = array();
	$this->params['data']['Media']['file']['tmp_name']=null;
	$this->params['data']['Media']['description'] = '';
	$this->setAction('edit');
	exit;
	}
	function edit($mid=0)
	{
		$uploaddir = ROOT.DS.'upload'.DS;
		
		$user = $this->checkSession(); // check user credentials
		$id = (int) $mid;
		
		if($id >= 0)
		{
			// check for uploaded data
			if (!empty($this->params['data']['Media']) &&
             is_uploaded_file($this->params['data']['Media']['file']['tmp_name']) && 
             ($this->params['data']['Media']['file']['error'] == 0))
        	{
				$toSave = $this->params['data']['Media']['file'];
				if($id != 0) $toSave['id'] = $id;
				$toSave['title'] = trim($this->params['data']['Media']['title']);
				$toSave['description'] = trim($this->params['data']['Media']['description']);
				$toSave['user_id'] = $user['User']['id'];
				$toSave['slug'] = slug2url($toSave['title']);

				list($toSave['width'],$toSave['height']) = getimagesize($this->params['data']['Media']['file']['tmp_name']);
				if(!$this->Media->save($toSave))
				{ echo 'fail';exit; /* failure */ }

				/* set my_id to the ID of the media */
				if($id == 0) $my_id = $this->Media->getLastInsertId();
				else $my_id = $id;

				/* now we save our tags seperated by SPACE and/or COMMA */

				//$this->Media->query(saveTags($this->params['data']['Media']['tags'],$my_id));
        		
				$uploadfile = $uploaddir . basename('file_'. $my_id);
				if (move_uploaded_file($this->params['data']['Media']['file']['tmp_name'], $uploadfile)) {
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
					createThumb($uploadfile);
					
					
					if($id==0) $this->flash('file uploaded successfully','/media/edit/'.$this->Media->getLastInsertId());
					else $this->flash('file changed successfully','/media/edit/'.$id);
				}
			}
			elseif(!empty($this->params['data']['Media']) && ($this->params['data']['Media']['url'] != ""))
			{
				include('Snoopy.class.php');
				$toSave = array();
				$toSave['title'] = trim($this->params['data']['Media']['title']);
				$toSave['description'] = trim($this->params['data']['Media']['description']);
				$toSave['url'] = trim($this->params['data']['Media']['url']);
				$toSave['user_id'] = $user['User']['id'];
				if($id != 0) $toSave['id'] = $id;
				$toSave['slug'] = slug2url($toSave['title']);
		
				$toSave['name'] = basename($toSave['url']);
				$snoopy = new Snoopy();
				$snoopy->maxlength = 4000000; // 4 mb
				$snoopy->agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7';
				$temp = parse_url($toSave['url']);
				$snoopy->referer = $temp['scheme'].'://'.$temp['host'].'/';
				$snoopy->fetch($toSave['url']);
				
				
				if($snoopy->status != 200) {echo 'Server returned bad status: '.$snoopy->status;exit;}

				// work with the temp file
				$tempfile = $uploaddir.basename('temp_'.md5($toSave['url']));
				$ofh = fopen($tempfile, 'w') or die("can't open file");
				fwrite($ofh,$snoopy->results);
				fclose($ofh); // close temp file
				
				// get file size
				$toSave['size'] = filesize($tempfile);
				if(!$toSave['size']) {echo 'temp filesize is 0';exit;}
				// get width and height of temp file
				$gisize = getimagesize($tempfile);
				list($toSave['width'],$toSave['height']) = $gisize;
				// get mime type
				$toSave['type'] = $gisize['mime'];
				
				// create file entry in database
				if(!$this->Media->save($toSave))
				{ echo 'fail';exit; /* failure */ }

				/* set my_id to the ID of the media */
				if($id == 0) $my_id = $this->Media->getLastInsertId();
				else $my_id = $id;
				
				// rename the temp file
				$uploadfile = $uploaddir . basename('file_'. $my_id);
				if(rename($tempfile,$uploadfile)) {
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
					createThumb($uploadfile);
					
					if($id==0) $this->flash('file uploaded successfully','/media/edit/'.$this->Media->getLastInsertId());
					else $this->flash('file changed successfully','/media/edit/'.$id);
				}
				else { echo 'oh fuck';exit;}
				exit;
				
			}
			elseif(!empty($this->params['data']['Media']))
			{
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				$this->params['data']['Media']['title'] = trim($this->params['data']['Media']['title']);
				$this->params['data']['Media']['slug'] = slug2url($this->params['data']['Media']['title']);
				
				$this->Media->save($this->params['data']['Media']);
				if($id==0) $id = $this->Media->getLastInsertId();
				/* now we save our tags seperated by SPACE and/or COMMA */
		//	$this->Media->query(saveTags($this->params['data']['Media']['tags'],$id));
			}
		}
		if($id>0) {
		$current = $this->Media->findById($id);
		$this->set('media',$current['Media']);
		}
	}
	function view($media_id=0)
	{
		$id = (int)$media_id;
		if($id>0) // id is only valid if >0
		{
			
			//echo '<img src="/media/download/'.$id.'"/>';
			$current = $this->Media->findById($id);
			if(!$current) {
				// file requested doesn't exist
				$this->cakeError('error404', array($this->params['url']));
				exit;
			}
			$prev = $this->Media->find('WHERE Media.id < '.$id,array('slug','title','id'),'Media.id DESC','LIMIT 1');
			$next = $this->Media->find('WHERE Media.id > '.$id,array('slug','title','id'),'Media.id ASC','LIMIT 1');
			
			$this->set('title', $current['Media']['title']);
			$this->set('media',$current['Media']);
			if($prev) $this->set('prev',$prev['Media']);
			if($next) $this->set('next',$next['Media']);
			//$this->set('id',$id);
			$this->Media->query('REPLACE INTO `views` ( `ip` , `media_id` ) VALUES ('.ip2long($_SERVER['REMOTE_ADDR']).', '.$id.');');
		}
		else // if ID = 0 or is negative, fuck it
		{
			// file requested doesn't exist
			$this->cakeError('error404', array($this->params['url']));
			exit;
		}
		
	}
	function thumb($id)
	{
		if($file = $this->Media->findById($id))
		{
			$uploaddir = ROOT.DS.'upload'.DS;
			// using basename as security for the hell of it
			$uploadfile = $uploaddir . basename('file_'.$file['Media']['id'].'_thumb');
			
			/* ETAG, quit if it hasnt changed */
//			$hash = strtotime($file['Media']['modified']) . '-' . md5('thumb'.$id);
//			header ("Etag: \"" . $hash . "\"");
			//if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $hash . '"')
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) == '"' . $file['Media']['modified'] . '"')  
			{
				// Return visit and no modifications, so do not send anything
				header("HTTP/1.0 304 Not Modified");
				header('Content-Length: 0');
				header('Expires: '.gmdate('D, d M Y H:i:s', time()+604800).' GMT');
				exit;
			}
			/* end ETAG */
			
			header('Content-type: '.$file['Media']['type']);
    		header('Content-Transfer-Encoding: Binary');
    		header('Content-length: '.filesize($uploadfile));
    		
    		// next 3 lines need checking
    		header('Last-Modified: '.$file['Media']['modified']);
    		header('Expires: '.gmdate('D, d M Y H:i:s', time()+604800).' GMT');
    		//header('Pragma: cache');

    		readfile($uploadfile);
			exit;
		}
		else {
			// file requested doesn't exist
			$this->cakeError('error404', array($this->params['url']));
			exit;
		}
		// exit out no matter what
		exit();
	}
	function sitemap()
	{
		$results = $this->Media->findAll(null,array('slug','modified'),'created DESC');
		$this->set('results',$results);
		$this->layout = null;
	}
    function download($id)
	{
		if($file = $this->Media->findById($id))
		{
			$uploaddir = ROOT.DS.'upload'.DS;
			// using basename as security for the hell of it
			$uploadfile = $uploaddir . basename('file_'. $file['Media']['id']);
			
			/* ETAG, quit if it hasnt changed */
			$hash = strtotime($file['Media']['modified']) . '-' . md5($id);
			header ("Etag: \"" . $hash . "\"");
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $hash . '"') 
			{
				// Return visit and no modifications, so do not send anything
				header("HTTP/1.0 304 Not Modified");
				header('Content-Length: 0');
				header('Expires: '.gmdate('D, d M Y H:i:s', time()+2592000).' GMT');
				exit;
			} 
			/* end ETAG */
			

			header('Content-type:'.$file['Media']['type']);
    		header('Content-Transfer-Encoding: Binary');
    		header('Content-length:'.$file['Media']['size']);
    		
    		header('Last-Modified:'.$file['Media']['modified']);
    		header('Expires: '.gmdate('D, d M Y H:i:s', time()+2592000).' GMT');
    	//	header('Pragma: cache');

    		readfile($uploadfile);

		}
		else {
			// file requested doesn't exist
			$this->cakeError('error404', array($this->params['url']));
		}
		// exit out no matter what
		exit();
	}

	var $paginate = array('limit' => 15, 'page' => 1);
	var $components = array('RequestHandler'); 
	var $uses = array('Media');
	// basically the same as index()
	function admin($page=1)
	{
		$user = $this->checkSession(); // kick em if they arent a user
		
		$page = (int) $page;
		if($page < 1) $page = 1;
		$limit = 30;

    	$result = $this->Media->findAll(null,array('id','slug','title','UNIX_TIMESTAMP(created)'),'created DESC',$limit,$page);
		if(!$result) $this->cakeError('error404',array($this->params['url']));
    	$this->set('results',$result);
    	$this->set('page',$page);
    	$this->set('title', 'Admin Area');
	}
    function index($page=1)
    {
    $page = (int) $page;
    if($page < 1) $page = 1;
    $limit = 60;
 //   $sql = 'SELECT id,name,UNIX_TIMESTAMP(created) as date FROM `media`  ORDER BY created ASC LIMIT '.$page*$limit.', '.$limit.';';
   // $result = $this->Media->query($sql);
    
    	$result = $this->Media->findAll(null,array('id','slug','title','UNIX_TIMESTAMP(created)'),'created DESC',$limit,$page);
    	if(sizeof($result)==0) $this->flash('You went past the end','/');
    	$this->set('results',$result);
    	$this->set('page',$page);
    	$this->set('title', 'Cool Pictures');
    /*	foreach($result as $r)
    	{
    		$uploaddir = ROOT.DS.'upload'.DS;
			// using basename as security for the hell of it
			$uploadfile = $uploaddir . basename('file_'.$r['Media']['id']);
			createThumb($uploadfile);
    	}*/
  //      $this->set('results', $this->paginate('Media'));
	}
}
?>
