﻿package Classes {		import flash.display.Sprite;	import flash.events.MouseEvent;	import flash.events.Event;	import Classes.Utilities.*;		public class ThumbContainer extends Sprite {						//create two booleans to track position of the container		public var canMoveLeft:Boolean = true;		public var canMoveRight:Boolean = false;				//check original x starting		//stage.stageWidth is the absolute right		public var originalPos:Number;		public var overContainer:Boolean;		public var speed:Number;				//multiplier for speed. increase to up scroll speed, decrease to slow down		public var multiplier:Number = 10;				public function ThumbContainer() {			//make sure the thumbcontainer is working			trace("| ---  Thumb Container On --- ---  |");						//set originalPosition of the thumbcontainer as a reference point			originalPos = this.x;						//listener to make sure that the mouse is over the thumbcontainer			this.addEventListener(MouseEvent.MOUSE_OVER, allowScroll);		}				//functions that initiate movement		public function moveLeft():void {			this.addEventListener(Event.ENTER_FRAME, leftMovement);		}				public function moveRight():void {			this.addEventListener(Event.ENTER_FRAME, rightMovement);		}				//functions that stop movement		public function stopLeftMove():void {			this.removeEventListener(Event.ENTER_FRAME, leftMovement);		}				public function stopRightMove():void {			this.removeEventListener(Event.ENTER_FRAME, rightMovement);		}				//function to move the thumbcontainer left		private function leftMovement(e:Event):void {						//check to make sure that the thumbContainer is not touching the edge. we don't want it to scroll on forever.			if(canMoveLeft && overContainer) {								//the speed is relative to how close the mouse is to the absolute right				speed = ( 130 - ( stage.stageWidth - stage.mouseX) ) / 130;												//for each frame, move thumContainer to the left speed*multiplier. speed is a decimal between 0 and 1				this.x -= (speed*multiplier);			}						//stop the movement if the thumbContainer moves past the stage edge			if( (this.x + this.width) <= stage.stageWidth ) {				canMoveLeft = false;				stopLeftMove();			} else {				canMoveLeft = true;			}		}						//function to move the thumbcontainer right				private function rightMovement(e:Event):void {						//check to make sure that the thumbContainer is not touching the original Position. we don't want it to scroll on forever.			if(canMoveRight && overContainer) {								//the speed is relative to how close the mouse is to the original position				speed = ( 130 - (stage.mouseX - originalPos) ) / 130;								//increment the x position each frame				this.x += (speed*multiplier);			}						//if the thumbcontainer moves farther than the originalposition, stop it			if(this.x >= this.originalPos) {				canMoveRight = false;				stopRightMove();			} else {				canMoveRight = true;			}		}				//the mouse is over the thumbContainer		private function allowScroll(e:MouseEvent):void {			this.removeEventListener(MouseEvent.MOUSE_OVER, allowScroll);			this.addEventListener(MouseEvent.MOUSE_OUT, disallowScroll);			overContainer = true;		}				//the mouse has lef tthe thumbcontainer		private function disallowScroll(e:MouseEvent):void {			this.addEventListener(MouseEvent.MOUSE_OVER, allowScroll);			this.removeEventListener(MouseEvent.MOUSE_OUT, disallowScroll);			overContainer = false;		}				//draw a container around all of the thumbnails so that MOUSE_OVER works. otherwise, the movieclip only exists where the thumbnails are and will break the scroll		public function drawContainer(pad:Number):void {						//create a new rectangle			var rect:Sprite = new Sprite();			rect.graphics.beginFill(0xB9CFE1);									//gives 20 pixels of padding and draw a container			//width + pad*2 because we're subtracting that away from the x pos			rect.graphics.drawRect(0, 0, this.width+(pad*2), this.height+40);			rect.x = -pad;			rect.y = -20;						//add rectangle behind everything			this.addChildAt(rect, 0);		}	}	}