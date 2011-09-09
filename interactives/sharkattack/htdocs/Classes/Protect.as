﻿package Classes {		import flash.display.Sprite;	import Classes.Utilities.*;	import flash.events.MouseEvent;	import flash.events.Event;		public class Protect extends Sprite {				//set vars to send to doc class		public var slideTitle:String;		public var slideContent:String;		public var slideNum:int;				//same thing but for survival box		public var surTitle:String;		public var surContent:String;		public var surNum:int;						public function Protect() {						//listeners for next/back btns			this.next_btn.addEventListener(MouseEvent.CLICK, nextSlides);			this.back_btn.addEventListener(MouseEvent.CLICK, backSlides);						//same thing but for survival box			this.next2_btn.addEventListener(MouseEvent.CLICK, next2Slides);			this.back2_btn.addEventListener(MouseEvent.CLICK, back2Slides);					}			//gets info from doc class telling what to load into slideshowcontentmc 		public function startLoad(sTitle:String, sCon:String):void{						slideTitle = sTitle;			slideContent = sCon;						//tells different information where to load			this.proTitle_txt.text = slideTitle;			this.proCon_txt.text = slideContent;								}				//gets info from doc class telling what to load into slideshowcontentmc 		public function startSurLoad(surTi:String, surCon:String):void{						surTitle = surTi;			surContent = surCon;						//tell text where to load			this.surTitle_txt.text = surTitle;			this.surCon_txt.text = surContent;								}				//dispatch event to doc class to view next slide		public function nextSlides(e:MouseEvent):void {			//set slidenum to move forward if next is clicked			slideNum +=  1;			//dispatch event to doc class to view next slide			dispatchEvent(new CustomEvent("nextMsg", this.slideNum));								}				public function backSlides(e:MouseEvent):void {			//subtract since moving backward when backbtn clicked			slideNum -=  1;			//dispatch event to doc class to view last slide			dispatchEvent(new CustomEvent("backMsg", this.slideNum));		}				//same two functions for survival slides		public function next2Slides(e:MouseEvent):void {			//set slidenum to move forward if next is clicked			surNum +=  1;			//dispatch event to doc class to view next slide			dispatchEvent(new CustomEvent("next2Msg", this.surNum));								}				public function back2Slides(e:MouseEvent):void {			//subtract since moving backward when backbtn clicked			surNum -=  1;			//dispatch event to doc class to view last slide			dispatchEvent(new CustomEvent("back2Msg", this.surNum));		}			}	}