jQuery(function(e){e.fn.hScroll=function(l){l=l||120,e(this).bind("DOMMouseScroll mousewheel",function(t){var i=t.originalEvent,n=i.detail?i.detail*-l:i.wheelDelta,o=e(this).scrollLeft();o+=n>0?-l:l,e(this).scrollLeft(o),t.preventDefault()})}});
$(document).ready(function(){
  $('.dock-container').hScroll(50);
});
