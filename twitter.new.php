<?php
// require_once($_SERVER["DOCUMENT_ROOT"].'/wp-blog-header.php');

require_once( 'class-wp-twitter-api.php' );


define("CONSUMER_KEY","xxxxxxxxx");
define("CONSUMER_SECRET","xxxxxxxx");


function getTwitterStatus_WP($userid,$count=1, $showDate=true, $userimg=false, $showUserName=false, $retweet=false, $join=false, $fullMonth=true, $beforeAfterTweet=array(false,"&laquo;&nbsp;", "&nbsp;&raquo;"), $delimiter="<span class=\"tweet-delimiter\"> - </span>"){

	setlocale(LC_ALL, 'fr_FR');

	// Set your personal data retrieved at https://dev.twitter.com/apps
	$credentials = array(
	  'consumer_key' => CONSUMER_KEY,
	  'consumer_secret' => CONSUMER_SECRET
	);

	// Let's instantiate Wp_Twitter_Api with your credentials
	$twitter_api = new Wp_Twitter_Api( $credentials );

	// Example a - Retrieve last 5 tweets from my timeline (default type statuses/user_timeline)
	$query = 'count='.$count.'&include_rts='.$retweet.'&screen_name='.$userid;
	// var_dump( $twitter_api->query( $query ) );

	$args = array(
	  'cache' => ( 24 * 60 * 60 )
	);

	$tweet_content = $twitter_api->query( $query,$args );
	// print_r($tweet_content);

	if(!empty($tweet_content)):
		foreach($tweet_content as $chose => $status){
			$text=trim(str_replace("’","'",$status->text));
			$date = str_replace('+0000', '', $status->created_at);
			// print_r($status->text);
			$interChange=null;
			foreach($status->entities as $type=> $param){

				// print_r($type);
				if(!empty($param)):
					foreach($param as $elem => $value){
						// print_r($value);

						switch($type){
						case "urls":
							$link='<a href="'.$value->url.'" target="_blank">'.$value->url.'</a>';
							break;
						case "hashtags":
							$link='<a href="https://twitter.com/search?q=%23'.$value->text.'" target="_blank">#'.$value->text.'</a>';
							break;
						case "user_mentions":
							$link='@<a href="http://twitter.com/'.$value->screen_name.'" target="_blank">'.$value->screen_name.'</a>';
							break;
						// case "creative":
						// 	$link='<a href="'.$value->url.'" target="_blank">'.$value->url.'</a>';
						// 	break;
						}
						$interChange[mb_substr($text, $value->indices[0], (($value->indices[1])-($value->indices[0])))]=$link;

						// echo mb_strlen($status->text)."--".$link;
						// echo "<br>\n";
						// echo substr($text, $value->indices[0]+2, (($value->indices[1])-($value->indices[0]))+1);
						// echo "<br><br>\n\n";
					}
				endif;
				
			}
			// print_r($interChange);

			$date = new DateTime($status->created_at);
			$date->format('Y-m-d H:i:sP') . "\n";
			$date->setTimezone(new DateTimeZone('EST'));
			$newdate=$date->format('Y-m-d H:i:sP') . "\n";
			
			//$newdate=getRelativeTime($newdate);
			$newdate=time2str($newdate, $date->format('Y-m-d'), $fullMonth);
		
			ob_start(); ?>
			<p>
				<?php if ($userimg!==false): ?>
				<span class="tweet-user-image"><img src="<?php echo $status->user->profile_image_url ?>" alt="<?php echo $status->user->screen_name ?>" /></span>
				<?php endif ?>

				<span class="tweet-content">

				<?php if ($beforeAfterTweet[0]===true): ?>
					<span class="beforeTweet"><?php echo $beforeAfterTweet[1] ?></span>
				<?php endif ?>

				<?php if ($showUserName===true): ?>
					<span class="tweet-user-name"><?php echo $status->user->name ?></span><?php echo $delimiter ?>
				<?php endif ?>

				<?php echo (!empty($interChange)?strtr($text,$interChange):$text) ?>

				<?php if ($beforeAfterTweet[0]===true): ?>
					<span class="afterTweet"><?php echo $beforeAfterTweet[2] ?></span>
				<?php endif ?>

				<?php if ($showDate===true): ?>
					<span class="tweet-date">
						<a href="http://twitter.com/<?php echo $userid ?>/status/<?php echo $status->id ?>" target="_blank"><?php echo utf8_encode($newdate) ?></a>
						<?php if ($retweet===true): ?>
							&bull; <a href="http://twitter.com/intent/tweet?in_reply_to=<?php echo $status->id ?>" target="_blank"><?php echo (SITELANG=="fr"?'répondre':"reply") ?></a>
							&bull; <a href="http://twitter.com/intent/retweet?tweet_id=<?php echo $status->id ?>" target="_blank"><?php echo (SITELANG=="fr"?'retweete':"retweet") ?></a>
							&bull; <a href="http://twitter.com/intent/favorite?tweet_id=<?php echo $status->id ?>" target="_blank"><?php echo (SITELANG=="fr"?'favoris':"favorites") ?></a>
						<?php endif ?>
					</span>
				<?php endif ?>

				</span>
			</p>
			<div class="clear"></div>
			<?php
			$new_content .= ob_get_contents();
			ob_end_clean();
		}
	else:
		switch(SITELANG){
			case 'en':
				$new_content = "Unable to connect";
			break;
			default:
			case 'fr':
				$new_content = "Connection impossible";
			break;
		}
	endif;

	if($join===true){
		$new_content .= "<div id=\"joindre-convertation\">
			<a href=\"http://twitter.com/\" target=\"_blank\" id=\"logo-twitter\"><img src=\"/images/twitter-logo.png\" /></a>
			<a href=\"http://twitter.com/".$userid."\" target=\"_blank\" id=\"joindre\">".(SITELANG=="fr"?'Joindre la conversation':"Join the conversation")."</a>
		</div>";
	}
	

	return $new_content;
}



function plural($num) {
	if ($num != 1)
		return "s";
}

function time2str($ts, $date, $fullMonth)
	{
		if(!ctype_digit($ts))
			$ts = strtotime($ts);
		$date = $date;
		$diff = time() - $ts;
		if($diff == 0){
			switch(SITELANG){
				case 'en':
					return 'now';
				break;
				default:
				case 'fr':
					return 'présentement';
				break;
			}	
		}elseif($diff > 0){
			$day_diff = floor($diff / 86400);
			if($day_diff == 0)
			{
				switch(SITELANG){
					case 'en':
						if($diff < 60) return 'just now';
						if($diff < 120) return '1 minute ago';
						if($diff < 3600) return floor($diff / 60) . ' minutes ago';
						if($diff < 7200) return '1 hour ago';
						if($diff < 86400) return floor($diff / 3600) . ' hours ago';
					break;
					default:
					case 'fr':
						if($diff < 60) return 'Il y a quelque seconde';
						if($diff < 120) return 'Il y a 1 minute';
						if($diff < 3600) return 'Il y a '.floor($diff / 60) . ' minutes';
						if($diff < 7200) return 'Il y a 1 heure';
						if($diff < 86400) return 'Il y a '.floor($diff / 3600) . ' heures';
					break;
				}
			}
			switch(SITELANG){
				case 'en':
					if($day_diff == 1) return 'Yesterday '.$date;
					if($fullMonth==true){
						if($day_diff < 7) return strftime("%d %B", strtotime($date));	
					}else{
						if($day_diff < 7) return strftime("%d %b", strtotime($date));//return $day_diff . ' days ago '.$date;
					}
					//if($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago '.$date;
					//if($day_diff < 60) return 'last month';
				break;
				default:
				case 'fr':
					if($day_diff == 1) return 'Hier '.$date;
					if($fullMonth==true){
						if($day_diff < 7) return strftime("%d %B", strtotime($date));	
					}else{
						if($day_diff < 7) return strftime("%d %b", strtotime($date));//return 'Il y a '.$day_diff . ' jours '.$date;
					}

					//if($day_diff < 31) return 'Il y a '.ceil($day_diff / 7) . ' semaines '.$date;
					//if($day_diff < 60) return 'last month';
				break;
			}
			
			if(date("Y")!=date("Y", strtotime($date))){
				if($fullMonth==true){
					return strftime("%d %B", strtotime($date)).' '.date("Y", strtotime($date));
				}else{
					return strftime("%d %b", strtotime($date)).' '.date("Y", strtotime($date));
				}
				
			}else{
				if($fullMonth==true){
					return strftime("%d %B", strtotime($date));	
				}else{
					return strftime("%d %b", strtotime($date));	
				}
			}
			//return $date;
			//return date('F Y', $ts);
		}
		/*else
		{
			$diff = abs($diff);
			$day_diff = floor($diff / 86400);
			if($day_diff == 0)
			{
				if($diff < 120) return 'in a minute';
				if($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
				if($diff < 7200) return 'in an hour';
				if($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
			}
			if($day_diff == 1) return 'Tomorrow';
			if($day_diff < 4) return date('l', $ts);
			if($day_diff < 7 + (7 - date('w'))) return 'next week';
			if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
			if(date('n', $ts) == date('n') + 1) return 'next month';
			return date('F Y', $ts);
		}*/
	}




$twitter_status = getTwitterStatus_WP("Capital_Image", 1, false, false, false, false, false, true, array(false,"",""));

?>

				<div class="tweet-container">
				<?php echo $twitter_status; ?>
				</div>