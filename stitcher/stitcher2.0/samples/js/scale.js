/*
 * jQuery.scale
 * 
 * Will resize an image to max/min width/height keeping the opposite dimension relative in scale.  
 * 0, blank and null will scale to original size by default.
 * Only width or height needs to be specified if scale is required.
 * percent is the size of the image between min/max that it will try and maintain in relation to its container
 * (ignores anchor containers) 
 * 
 * maxHeight
 * minHeight
 * maxWidth
 * minWidth
 * percentHeight
 * percentWidth
 * 
 */

if(jQuery) (function($) {
	
	//jQuery.scale
	$.fn.scale = function(options) 
	{
		return this.each(function() 
		{
			$(window).resize(function () {
				//the plan
				//  bind a resize to each element.
				//  have the resize call the imageSize function?
				//  store each this (element) in jQuery.scale.element?
				//  
			});
			
		});
		$(window).resize();
	};
	
	function imageSize(obj, callback, force)
	{   force = (typeof force == 'undefined')? true: force;
		obj = $(obj);
	    if(obj.get(0).tagName!='IMG') return; //confirm calling jQuery image properties is safe
		if(!(obj.width() && obj.height())) return; //  is null, undefined, NaN, empty string, 0, false
		if(!force)
		{	callback(obj, obj.width(), obj.height());
		}
		else
		{	//pull original to get width/height (generally recommended)	
			var theImage = new Image();
			theImage.src = obj.attr("src");
			if(theImage.complete){			
				callback(obj, theImage.width,theImage.height);
			} else {
				$(theImage).load(function(){			
					callback(obj, theImage.width,theImage.height);
				});
			}
		}
	}
	
	function aspectRatio(originalW,originalH)
	{	//assumes positive ints are passed - no checks
		this.w=(originalW)?originalW:0;
		this.h=(originalH)?originalH:0;
		this.currentW=this.w;
		this.currentH=this.h;
		this.resize = function(tw,th,crop,grow)
		{	
				if(!(this.currentW && this.currentH && tw && th)) return;
				if(!grow && (this.currentW<=tw && this.currentH<=th)
					|| crop && (this.currentW<=tw || this.currentH<=th)
				  )
				{
					this.w = this.currentW; this.h = this.currentH;
					return;
				}
				nh = Math.round(tw * (this.currentH / this.currentW));
				nw = Math.round(th * (this.currentW / this.currentH));
				if((nh<=th && !crop) || (nh>=th && crop))
				{
					this.w = tw;
					this.h = nh;
				}		
				else
				{
					this.w = nw;
					this.h = th;
				}
		};
	}
	
})(jQuery);