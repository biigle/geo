biigle.$declare("geo.events",new Vue),biigle.$viewModel("geo-image-location-panel",function(e){var t=biigle.$require("geo.image");new Vue({el:e,data:{images:[t]},components:{imageMap:biigle.$require("geo.components.imageMap")}})}),biigle.$viewModel("geo-map",function(e){var t=biigle.$require("geo.events"),i=biigle.$require("geo.volume.id"),l=biigle.$require("geo.api.imageWithLabel"),a=biigle.$require("messages.store");new Vue({el:e,data:{allImages:biigle.$require("geo.images"),filteredImages:[],selectedLabels:[],key:"biigle.geo.imageSequence."+i,filteredImageCache:{}},computed:{selectedImages:function(){return JSON.parse(localStorage.getItem(this.key))||[]},images:function(){if(this.selectedLabels.length>0){var e=this;return this.allImages.filter(function(t){return e.filteredImages.indexOf(t.id)!==-1})}return this.allImages}},components:{imageMap:biigle.$require("geo.components.imageMap")},methods:{handleSelectedImages:function(e){e.length>0?localStorage.setItem(this.key,JSON.stringify(e)):localStorage.removeItem(this.key)},addSelectedLabel:function(e){this.selectedLabels.indexOf(e.id)===-1&&(this.selectedLabels.push(e.id),this.updateFilteredImages())},handleSelectedLabel:function(e){this.filteredImageCache.hasOwnProperty(e.id)?this.addSelectedLabel(e):(t.$emit("loading.start"),l.get({tid:i,lid:e.id},{}).bind(this).then(function(t){this.filteredImageCache[e.id]=t.data,this.addSelectedLabel(e)},function(t){this.handleDeselectedLabel(e),a.handleErrorResponse(t)}).finally(function(){t.$emit("loading.stop")}))},handleDeselectedLabel:function(e){var t=this.selectedLabels.indexOf(e.id);t!==-1&&(this.selectedLabels.splice(t,1),this.updateFilteredImages())},handleClearedLabels:function(){this.selectedLabels.splice(0),this.updateFilteredImages()},updateFilteredImages:function(){var e;if(this.filteredImages.splice(0),this.selectedLabels.length>0){e=this.filteredImageCache[this.selectedLabels[0]],Array.prototype.push.apply(this.filteredImages,e);for(var i=this.selectedLabels.length-1;i>=0;i--){e=this.filteredImageCache[this.selectedLabels[i]];for(var l=e.length-1;l>=0;l--)this.filteredImages.indexOf(e[l])===-1&&this.filteredImages.push(e[l])}}this.$nextTick(function(){t.$emit("imageMap.update",this.images)})}},created:function(){t.$on("label.selected",this.handleSelectedLabel),t.$on("label.deselected",this.handleDeselectedLabel),t.$on("label.cleared",this.handleClearedLabels)}})}),biigle.$viewModel("geo-navbar",function(e){var t=biigle.$require("geo.events"),i=biigle.$require("geo.images");new Vue({el:e,data:{number:i.length,loading:!1},created:function(){var e=this;t.$on("loading.start",function(){e.loading=!0}),t.$on("loading.stop",function(){e.loading=!1}),t.$on("imageMap.update",function(t){e.number=t.length})}})}),biigle.$viewModel("geo-sidebar",function(e){var t=biigle.$require("geo.events");new Vue({el:e,data:{labelTrees:biigle.$require("geo.labelTrees")},components:{sidebar:biigle.$require("core.components.sidebar"),sidebarTab:biigle.$require("core.components.sidebarTab"),labelTrees:biigle.$require("labelTrees.components.labelTrees")},methods:{handleSidebarToggle:function(){this.$nextTick(function(){t.$emit("sidebar.toggle")})},handleSelect:function(e){t.$emit("label.selected",e)},handleDeselect:function(e){t.$emit("label.deselected",e)},handleCleared:function(){t.$emit("label.cleared")}}})}),biigle.$declare("geo.api.imageWithLabel",Vue.resource("api/v1/volumes{/tid}/images/filter/annotation-label{/lid}",{})),biigle.$component("geo.components.imageMap",{template:'<div class="image-map"></div>',props:{images:{type:Array,required:!0},preselected:{type:Array,default:function(){return[]}},interactive:{type:Boolean,default:!0},zoom:{type:Number},selectable:{type:Boolean,default:!1}},data:function(){return{source:new ol.source.Vector}},computed:{features:function(){for(var e=[],t=this.images.length-1;t>=0;t--)e.push(new ol.Feature({id:this.images[t].id,preselected:this.preselected.indexOf(this.images[t].id)!==-1,geometry:new ol.geom.Point(ol.proj.fromLonLat([this.images[t].lng,this.images[t].lat]))}));return e}},methods:{parseSelectedFeatures:function(e){return e.getArray().map(function(e){return e.get("id")})},updateFeatures:function(){this.source.clear(),this.source.addFeatures(this.features)}},created:function(){var e=biigle.$require("geo.events");e.$on("imageMap.update",this.updateFeatures)},mounted:function(){var e=biigle.$require("geo.ol.style"),t=biigle.$require("geo.events"),i=this;this.source.addFeatures(this.features);var l=this.source.getExtent(),a=new ol.layer.Tile({source:new ol.source.OSM}),o=new ol.layer.Vector({source:this.source,style:e.default,updateWhileAnimating:!0,updateWhileInteracting:!0}),n=new ol.Map({target:this.$el,layers:[a,o],view:new ol.View,interactions:ol.interaction.defaults({altShiftDragRotate:!1,doubleClickZoom:this.interactive,keyboard:this.interactive,mouseWheelZoom:this.interactive,shiftDragZoom:!1,dragPan:this.interactive,pinchRotate:!1,pinchZoom:this.interactive}),controls:ol.control.defaults({zoom:this.interactive})});if(n.getView().fit(l,n.getSize()),this.zoom&&n.getView().setZoom(this.zoom),this.interactive&&(n.addControl(new ol.control.ScaleLine),n.addControl(new ol.control.ZoomToExtent({extent:l,label:""})),n.addControl(new ol.control.OverviewMap({collapsed:!1,collapsible:!1,layers:[a],view:new ol.View({zoom:1,maxZoom:1})}))),this.selectable){var s=new ol.interaction.Select({style:e.selected,features:this.features.filter(function(e){return e.get("preselected")})}),r=s.getFeatures();n.addInteraction(s),s.on("select",function(e){i.$emit("select",i.parseSelectedFeatures(r))});var d=new ol.interaction.DragBox({condition:ol.events.condition.platformModifierKeyOnly});n.addInteraction(d),d.on("boxend",function(){r.clear(),i.source.forEachFeatureIntersectingExtent(d.getGeometry().getExtent(),function(e){r.push(e)}),i.$emit("select",i.parseSelectedFeatures(r))})}t.$on("sidebar.toggle",function(){n.updateSize()}),this.$nextTick(function(){n.updateSize()})}}),biigle.$declare("geo.ol.style",function(){var e={colors:{blue:"#0099ff",white:"#ffffff",orange:"#ff5e00"},radius:{default:6},strokeWidth:{default:2}};return ol&&(e.default=new ol.style.Style({image:new ol.style.Circle({radius:e.radius.default,fill:new ol.style.Fill({color:e.colors.blue}),stroke:new ol.style.Stroke({color:e.colors.white,width:e.strokeWidth.default})})}),e.selected=new ol.style.Style({image:new ol.style.Circle({radius:e.radius.default,fill:new ol.style.Fill({color:e.colors.orange}),stroke:new ol.style.Stroke({color:e.colors.white,width:e.strokeWidth.default})}),zIndex:1/0})),e});