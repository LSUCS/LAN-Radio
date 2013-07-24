<?php

Nebula::requireLibrary('NBBC');

class NebBBCode {
	public $smilies;
	
	function __construct(){
		$this->smilies = array(
                        "smile.png" => array(":)",":-)",":smile:"),
                        "frown.png" => array(":(", ":-(", ":sad:", ":sadface:"),
                        "big_grin.png" => array(":D", ":D", ":-D", ":bigsmile"),
                        "tongue.png" => array(":P", ":p", ":-P", ":-p", ":tongue:"),
                        "sunglasses.png" => array("8)", "8-)", "B)", "B-)", ":cool"),
                        "wink.png" => array(";)", ";-)", ":wink:"),
                        "wink_tongue.png" => array(";P", ";p"),
                        "crying.png" => array(";(", ";-(", ":'(", ":'-(", ":cry:"),
                        "agape.png" => array(":o", ":O", ":-o", ":-O", ":gape:", ":gasp:"),
                        "mad.png" => array(">.<", ">_<", ":mad:"),
                        "dead.png" => array("x_x", "X_x", "x_X", "x.x", "X.x", "x.X", ":dead:"),
                        "not_even.png" => array(":noteven:"),
                        "sick.png" => array(":sick:"),
                        "yawn.png" => array(":yawn:"),
                        "bashful.png" => array("-.-", "-_-"),
                        "not_entertained.png" => array(":|", ":-|"),
                        "zipped.png" => array(":x", ":X", ":zipped:"),
                        "smitten.png" => array("^.^", "^_^", ":smitten:"),
                        "nerdy.png" => array(":nerd:"),
                        "big_grin_evil.png" => array(":evilgrin:", ">:D"),
						"angry.png" => array(":angry:", ">_<"),
						"asleep.png" => array(":asleep:", "I-|"),
						"asleep_2.png" => array(":asleep2:", "I-)"),
						"bashful_cute.png" => array(":bashfulcute:", "=>_>=", "=<_<=", "=>.>=", "=<.<="),
						"bashful_cute_2.png" => array(":bashfulcute2:", "=^_^=", "=^.^="),
						"big_grin_squint.png" => array(":biggrinsquint:", "XD", "xD", "X-D", "x-D"),
						"big_grin_wink.png" => array(":biggrinwink:", ";D", ";-D"),
						"bored.png" => array(":bored:", ":|", ":-|"),
						"confused.png" => array(":confused:", ":s", ":S", ":?", ":-s", ":-S", ":-?"),
						"delicious.png" => array(":delicious:"),
						"don't_cry.png" => array(":dontcry:"),
						"evil.png" => array(":evil:", "]:)", "]:-)"),
						"evil_grin.png" => array(":evilgrin:", ">:-)", ">:)", ">:]"),
						"evil_invert.png" => array(":evil2:", ">:D", ">:-D"),
						"grin.png" => array(":grin:", ":^D"),
						"impatient.png" => array(":impatient:"),
						"inlove.png" => array(":inlove:", "L_L"),
						"kiss.png" => array(":kiss:", ":*"),
						"little_laugh.png" => array(":lillaugh:"),
						"oh_rly.png" => array(":ohrly:"),
						"sarcasm.png" => array(":sarcasm:"),
						"shocked.png" => array(":shock:", "O_O", "@_@", "O.O", "@.@"),
						"silly.png" => array(":silly:", "XP", "xP", "x-P", "X-P"),
						"sing.png" => array(":sing:", "^o^"),
						"smug.png" => array(":smug:"),
						"sour.png" => array(":sour:", "xS", "XS", "x-S", "X-S"),
						"stress.png" => array(":stress:", "':|"),
						"whistle.png" => array(":whistle:"),
						"shark.gif" => array(":shark:"),
						"penguin.png" => array(":penguin:"),
						"heart.png" => array(":heart:", "<3"),
					);
	}
	function parse($text){
		/* Initiate NBBC */
		$bbcode = new BBCode;
		
		/* Options */
		$bbcode->SetDetectURLs(true);
		$bbcode->ClearSmileys();
		$bbcode->SetSmileyDir(STATIC_SERVER."common/emoticons");
		$bbcode->SetSmileyURL(STATIC_SERVER."common/emoticons");
		
		/* Custom Smileys */
		foreach($this->smilies AS $image=>$symbols){
			foreach($symbols AS $symbol){
				$bbcode->AddSmiley($symbol, $image);
			}
		}
		/* End Smileys */
		
		/* return text */
		return $bbcode->Parse($text);
	}
	
	function smileyTable(){
		print "<table width=\"75%\"><tr><td>Smiley</td><td>Symbols</td></tr>";
		foreach($this->smilies AS $image=>$symbols){
			print "<tr><td><img src=\"".STATIC_SERVER."common/emoticons/$image\"/></td><td>";
			foreach($symbols AS $symbol){
				print "$symbol &nbsp;&nbsp;&nbsp;";
			}
			print "</td></tr>";
		}
		print "</table>";
	}
}

?> 