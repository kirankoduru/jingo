<?php  

require_once 'header.php' ; 

$query = "SELECT * FROM USER WHERE username = '".$username."'";
$result = $mysqli->query($query);

// fetch associative array of the result
$row = $result->fetch_array();

$firstname = $row['firstname'];
$lastname = $row['lastname'];

$uid = $row['uid'];


// get the count of notes from the user with uid
$query3 = "SELECT COUNT(nid) AS ncount FROM NOTE WHERE uid='".$uid."'";
$result=$mysqli->query($query3);
$row3 = $result->fetch_array();

// get the follower count
$query_follower_count = "SELECT COUNT(followeruid) as fcount FROM FRIENDSHIP WHERE leaderuid = '".$uid."'";
$result_follower_count = $mysqli->query($query_follower_count);
$row_follower_count = $result_follower_count->fetch_array();
// echo $result_follower_count;exit;


// when submitting the note through the same page
if($_POST) {
	// echo "<pre>";print_r($_POST);
	
	$note = addslashes($_POST['note']);

	$range = $_POST['radius'];

	if($_POST['tag_name'] || $_POST['tag_name2'] || $_POST['tag_name3']) {
		$tag_name= $_POST['tag_name'];
		$tag_name2= $_POST['tag_name2'];
		$tag_name3= $_POST['tag_name3'];

	}else {

		$tag_name = $tag_name2 = $tag_name3 = '';
	}	

	// user co-ordinates
	$lat = $_POST['lat'];
	$lon = $_POST['lon'];
	$radius = $_POST['radius'];
	$x1 = $lat + $radius * 0.869 / 60 ;
	$y1 = $lon + $radius * 0.869 / 60 ;

	// insert note in DB
	$query = "INSERT INTO NOTE (uid,notetext,x,y,x1,y1,radius,hyperlink)  VALUES ('".$uid."','".$note."',$lat,$lon,$x1,$y1,'".$radius."','".$tag_name.",".$tag_name2.",".$tag_name3."');";
	 // echo $query;exit;
	$mysqli->query($query) or die(mysql_errno());

	/*
	============
	  SCHEDULE
	============
	scid - PRIMARY KEY,
	nid - note id,
	timefrom - time from,
	timeto - time to,
	datefrom - date from,
	dateto - date to,
	repeatday - Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday ,
	*/

	if ($_POST['timefrom'] && $_POST['timeto'] && $_POST['datefrom'] && $_POST['dateto'] && $_POST['repeatday']) {
		
		$timefrom= $_POST['timefrom'];
		$timeto = $_POST['timeto'];
		$datefrom = $_POST['datefrom'];
		$dateto =  $_POST['dateto'];
		$repeatday =  $_POST['repeatday'];
		
		//Get last inserted id
		$inserted_nid = $mysqli->insert_id;

		$query_schedule = "INSERT INTO SCHEDULE_DATE (`nid`, `timefrom`, `timeto` , `datefrom`, `dateto`, `repeatday`)
						    VALUES ('$inserted_nid','$timefrom','$timeto','$datefrom','$dateto','$repeatday')" ;
		//echo $query_schedule;
		$mysqli->query($query_schedule) or die(mysql_errno());

	}
	

}

// get all notes from the current user
 $query2 = "SELECT nid,notetext,hyperlink,Utime,Nlike,Nfav FROM NOTE WHERE uid = '".$uid."' ORDER BY nid DESC;";
// $query2 = "select distinct nid,notetext
// 			from 
// 			(SELECT F.uid, tag, F.x Fx,F.y Fy, A.x,A.y,A.x1,A.y1,timefrom,timeto,repeatday
// 			       from FILTER F, ADDRESS A NATURAL JOIN SCHEDULE_USER S
// 			       WHERE F.uid=S.uid and F.Aname=A.Aname and F.uid=$uid 
// 			and current_time between timefrom and timeto and (DAYNAME(current_date)=repeatday or repeatday='Any')
// 			and F.x between A.x and A.x1 and F.y between A.y and A.y1) A,
// 			(SELECT NOTE.nid,notetext,x,y,x1,y1,tag,timefrom, timeto,datefrom,dateto,repeatday
// 			FROM NOTE LEFT JOIN NOTE_TAG N ON NOTE.nid=N.nid LEFT JOIN SCHEDULE_DATE S ON NOTE.nid=S.nid, FRIENDSHIP F
// 			where NOTE.uid=F.leaderuid and F.followeruid=$uid and current_time between timefrom and timeto  and current_date between datefrom 
// 			and dateto and (DAYNAME(current_date)=repeatday or repeatday='Any')
// 			) B
// 			where A.Fx between B.x and B.x1 AND A.Fy between B.y and B.y1 and A.tag=B.tag ;
// 			";
// echo $query2;
$result2 = $mysqli->query($query2) or die(mysql_errno());

require_once ('time_ago.php');

?>

<?php if($_SESSION['loggedin']) { ?>

<style type="text/css">

#mapCanvas {
    width: 225px;
    height: 200px;
    float: left;
 }
#infoPanel {
float: left;
margin-left: 10px;
}
#infoPanel div {
margin-bottom: 5px;
}

</style>

<div class="span3 well">
	<div class="row">
		<div class="span1"><a href="#" class="thumbnail"><img src="include/img/users/user.jpg" alt=""></a></div>
		<div class="span2">
			<p><a href="#">@<?php echo $username ; ?></a></p>
          	<p><strong><?php echo $firstname.' '.$lastname ?></strong></p>
		</div>
		<div class="span3 mt10">
			<span class=" badge badge-warning"><?php echo $row3['ncount'] ;?> notes</span> 
			<span class=" badge badge-info"><?php echo $row_follower_count['fcount']?> followers</span>
			<a href="set_filters.php"><span class="badge badge-inverse">filters</span></a>
		</div>
		<div class="span4 mt10">
		    <form accept-charset="UTF-8" action="userhome.php" id="post-note" method="POST">
		    	<!-- hidden type for location-->
		    	<!-- <input type="hidden" value="" name="lat" id="lat"/>
		    	<input type="hidden" value="" name="lon" id="lon"/> -->

		        <textarea class="span3" id="new_message" name="note"
		        placeholder="Type in your message" rows="3"></textarea>

		        <div class="clear-fix"></div>
		        <span class="clickid badge" name="range">location</span>
				<span class="clickid badge" name="tag">tag</span>
				<span class="clickid badge" name="schedule">schedule</span>
				<span class="clickid badge" name="me">#me</span>
				<h6>140 characters remaining</h6>

				<div class="span3" style="margin:0">
				<!-- div#range-->
				<div id="range" style="display:none;">
					radius:	<input type="text" name="radius" class="input-medium" value="" maxlength="100" />
					<div id="mapCanvas"></div>
					<div id="infoPanel">
					    
					    <div id="info"></div>
					    <div id="markerStatus"><i>Click and drag the marker.</i></div>
					    <label for="latitude">latitude:</label>
					   <input id="lat" type="text" name="lat" value="" maxlength="100" />
					   <label for="longitude">longitude:</label>
					   <input id="lon" type="text" name="lon" value="" maxlength="100" />
					    <b>Closest matching address:</b>
					    <input id="address" type="text" name="address" value="" maxlength="100" ><br/>
					 </div>
				</div>

				<!-- div#tag-->
				<div id="tag" style="display:none;">
					tag : <input type="text" name="tag_name" class="input-small" value="" maxlength="100" />
					<span class="clickid btn btn-inverse mb10" name="tag2">Add</span>
				</div>

				<!-- div#tag2-->
				<div id="tag2" style="display:none;">
					tag : <input type="text" name="tag_name2" class="input-small" value="" maxlength="100" />
					<span class="clickid btn btn-inverse mb10"  name="tag3">Add</span>
				</div>

				<!-- div#tag3-->
				<div id="tag3" class="clickid" style="display:none;">
					tag : <input type="text" name="tag_name3" class="input-small" value="" maxlength="100" />
				</div>

				<!-- div#schedule -->
				<div id="schedule" class="clickid" style="display:none;">
					Time from :
					<div id="datetimepicker1" class="input-append">
						<input data-format="hh:mm:ss" type="text" class="input-small" name="timefrom" value="" placeholder="00:00:00" maxlength="100">
						<span class="add-on">
							<i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-time"></i>
					    </span>
			  		</div>
			  		<br/>

			  		Time to :&nbsp;&nbsp;&nbsp;&nbsp;
			  		<div id="datetimepicker2" class="input-append">
						<input data-format="hh:mm:ss" type="text" class="input-small" name="timeto" value="" placeholder="00:00:00" maxlength="100">
						<span class="add-on">
							<i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-time"></i>
					    </span>
			  		</div>

			  		Date from :
			  		<div id="datefrompicker1" class="input-append">
			  			<input data-format="yyyy-MM-dd" name="datefrom" class="input-small" value="" type="text"></input>
				  		<span class="add-on">
					      <i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i>
					    </span>
					</div>

					Date to :&nbsp;&nbsp;&nbsp;&nbsp;
			  		<div id="datetopicker1" class="input-append">
			  			<input data-format="yyyy-MM-dd" name="dateto" class="input-small" value="" type="text"></input>
				  		<span class="add-on">
					      <i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i>
					    </span>
					</div>

			  		<label for="repeatday">Repeat:</label>
					<select name="repeatday">
					  <option value ="Any">Any</option>  <!--any refer to null in the database-->
					  <option value ="Monday">Monday</option>
					  <option value ="Tuesday">Tuesday</option>
					  <option value ="Wednesday">Wednesday</option>
					  <option value ="Thursday">Thursday</option>
					  <option value ="Friday">Friday</option>
					  <option value ="Saturday">Saturday</option>
					  <option value ="Sunday">Sunday</option>
					</select>
				</div><!-- end div#schedule -->
				 </div><!-- end div.span3-->
			  <button class="btn btn-success" type="submit">Post Note</button>
		    </form>

		</div>
	</div>
</div>

<div class="span6 well">
	<h2>Notes</h2>



	<?php while( $row = $result2->fetch_array(MYSQLI_ASSOC))  { ?>
	

	<div class="row">
		<div class="span1">
			<a href="#" class="thumbnail">
				<img src="include/img/users/user.jpg" alt="">
			</a>
		</div>
		<div class="span5">
			<p><?php echo $firstname.' '.$lastname ; ?> <a href="#">@<?php echo $username ; ?></a> 
			<span class="pull-right"><small><?php echo time_ago(strtotime($row['Utime']));?></small></span>
			</p>
          	<p><?php echo stripslashes($row['notetext']) ; ?></p>
          	<p>
          		<?php 
          			$tags = explode(',', $row['hyperlink']);
          			foreach ($tags as $key) {
          				echo "<a href='#'>".$key."</a> ";
          			} 
          		?>
          	</p>
          	<div>

          		<a href="#" class="like-button" name="<?php echo $row['nid'] ;?>"><span class="icon-heart"></span> <?php echo $row['Nlike']?> Like</a>
          		<a id="com" href="#"><span class="icon-comment"></span> Comment</a>
          		<a href="#" class="fav-button" name="<?php echo $row['nid'] ;?>">
          			<?php if($row['Nfav'] == 1 ) { 
          				echo "<span class='icon-star icon-white'></span> Favorited" ;
          			}else {
          				echo "<span class='icon-star'></span> Favorite" ;
          			}?>
          		</a>

			</div>
		<!-- begin htmlcommentbox.com -->
		 <div style="display:none" id="HCB_comment_box"><a href="http://www.htmlcommentbox.com">HTML Comment Box</a> is loading comments...</div>
		 <link rel="stylesheet" type="text/css" href="//www.htmlcommentbox.com/static/skins/bootstrap/twitter-bootstrap.css?v=0" />
		 <script type="text/javascript" id="hcb"> /*<!--*/
		  if(!window.hcb_user){hcb_user={};}
		   (function(){var s=document.createElement("script"), 
		   	l=(""+window.location || hcb_user.PAGE), h="//www.htmlcommentbox.com";
		   	s.setAttribute("type","text/javascript");
		   	s.setAttribute("src", h+"/jread?page="+encodeURIComponent(l).replace("+","%2B")+"&opts=16862&num=10");
		   	if (typeof s!="undefined") document.getElementsByTagName("head")[0].appendChild(s);})(); /*-->*/ 
		   	</script>
		<!-- end htmlcommentbox.com -->
   
		</div>
	</div>
	<hr>
	<?php } ?>
	

	
</div>


<?php } else { 

	header('Location:create_new_user.php') ; 

} ?>


<?php require_once('footer.php'); ?>