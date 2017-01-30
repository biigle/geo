biigle.$declare("geo.events",new Vue),biigle.$viewModel("geo-image-location-panel",function(e){var t=biigle.$require("geo.image");new Vue({el:e,data:{images:[t]},components:{imageMap:biigle.$require("geo.components.imageMap")}})}),biigle.$viewModel("geo-map",function(e){var t=biigle.$require("geo.events"),i=biigle.$require("geo.volume.id"),n=biigle.$require("geo.api.imageWithLabel"),o=biigle.$require("messages.store");new Vue({el:e,data:{allImages:biigle.$require("geo.images"),filteredImages:[],selectedLabels:[],key:"biigle.geo.imageSequence."+i,filteredImageCache:{}},computed:{selectedImages:function(){return JSON.parse(localStorage.getItem(this.key))||[]},images:function(){if(this.selectedLabels.length>0){var e=this;return this.allImages.filter(function(t){return e.filteredImages.indexOf(t.id)!==-1})}return this.allImages}},components:{imageMap:biigle.$require("geo.components.imageMap")},methods:{handleSelectedImages:function(e){e.length>0?localStorage.setItem(this.key,JSON.stringify(e)):localStorage.removeItem(this.key)},addSelectedLabel:function(e){this.selectedLabels.indexOf(e.id)===-1&&(this.selectedLabels.push(e.id),this.updateFilteredImages())},handleSelectedLabel:function(e){this.filteredImageCache.hasOwnProperty(e.id)?this.addSelectedLabel(e):(t.$emit("loading.start"),n.get({tid:i,lid:e.id},{}).bind(this).then(function(t){this.filteredImageCache[e.id]=t.data,this.addSelectedLabel(e)},function(t){this.handleDeselectedLabel(e),o.handleErrorResponse(t)}).finally(function(){t.$emit("loading.stop")}))},handleDeselectedLabel:function(e){var t=this.selectedLabels.indexOf(e.id);t!==-1&&(this.selectedLabels.splice(t,1),this.updateFilteredImages())},handleClearedLabels:function(){this.selectedLabels.splice(0),this.updateFilteredImages()},updateFilteredImages:function(){var e;if(this.filteredImages.splice(0),this.selectedLabels.length>0){e=this.filteredImageCache[this.selectedLabels[0]],Array.prototype.push.apply(this.filteredImages,e);for(var i=this.selectedLabels.length-1;i>=0;i--){e=this.filteredImageCache[this.selectedLabels[i]];for(var n=e.length-1;n>=0;n--)this.filteredImages.indexOf(e[n])===-1&&this.filteredImages.push(e[n])}}this.$nextTick(function(){t.$emit("imageMap.update",this.images)})}},created:function(){t.$on("label.selected",this.handleSelectedLabel),t.$on("label.deselected",this.handleDeselectedLabel),t.$on("label.cleared",this.handleClearedLabels)}})}),biigle.$viewModel("geo-navbar",function(e){var t=biigle.$require("geo.events"),i=biigle.$require("geo.images");new Vue({el:e,data:{number:i.length,loading:!1},created:function(){var e=this;t.$on("loading.start",function(){e.loading=!0}),t.$on("loading.stop",function(){e.loading=!1}),t.$on("imageMap.update",function(t){e.number=t.length})}})}),biigle.$viewModel("geo-sidebar",function(e){var t=biigle.$require("geo.events");new Vue({el:e,data:{labelTrees:biigle.$require("geo.labelTrees")},components:{sidebar:biigle.$require("geo.components.sidebar"),sidebarTab:biigle.$require("geo.components.sidebarTab"),labelTrees:biigle.$require("labelTrees.components.labelTrees")},methods:{handleSidebarToggle:function(){this.$nextTick(function(){t.$emit("sidebar.toggle")})},handleSelect:function(e){t.$emit("label.selected",e)},handleDeselect:function(e){t.$emit("label.deselected",e)},handleCleared:function(){t.$emit("label.cleared")}}})}),biigle.$declare("geo.api.imageWithLabel",Vue.resource("/api/v1/volumes{/tid}/images/filter/annotation-label{/lid}",{})),biigle.$component("geo.components.imageMap",{template:'<div class="image-map"></div>',props:{images:{type:Array,required:!0},preselected:{type:Array,default:function(){return[]}},interactive:{type:Boolean,default:!0},zoom:{type:Number},selectable:{type:Boolean,default:!1}},data:function(){return{source:new ol.source.Vector}},computed:{features:function(){for(var e=[],t=this.images.length-1;t>=0;t--)e.push(new ol.Feature({id:this.images[t].id,preselected:this.preselected.indexOf(this.images[t].id)!==-1,geometry:new ol.geom.Point(ol.proj.fromLonLat([this.images[t].lng,this.images[t].lat]))}));return e}},methods:{parseSelectedFeatures:function(e){return e.getArray().map(function(e){return e.get("id")})},updateFeatures:function(){this.source.clear(),this.source.addFeatures(this.features)}},created:function(){var e=biigle.$require("geo.events");e.$on("imageMap.update",this.updateFeatures)},mounted:function(){var e=biigle.$require("geo.ol.style"),t=biigle.$require("geo.events"),i=this;this.source.addFeatures(this.features);var n=this.source.getExtent(),o=new ol.layer.Tile({source:new ol.source.OSM}),a=new ol.layer.Vector({source:this.source,style:e.default,updateWhileAnimating:!0,updateWhileInteracting:!0}),s=new ol.Map({target:this.$el,layers:[o,a],view:new ol.View,interactions:ol.interaction.defaults({altShiftDragRotate:!1,doubleClickZoom:this.interactive,keyboard:this.interactive,mouseWheelZoom:this.interactive,shiftDragZoom:!1,dragPan:this.interactive,pinchRotate:!1,pinchZoom:this.interactive}),controls:ol.control.defaults({zoom:this.interactive})});if(s.getView().fit(n,s.getSize()),this.zoom&&s.getView().setZoom(this.zoom),this.interactive&&(s.addControl(new ol.control.ScaleLine),s.addControl(new ol.control.ZoomToExtent({extent:n,label:""})),s.addControl(new ol.control.OverviewMap({collapsed:!1,collapsible:!1,layers:[o],view:new ol.View({zoom:1,maxZoom:1})}))),this.selectable){var l=new ol.interaction.Select({style:e.selected,features:this.features.filter(function(e){return e.get("preselected")})}),r=l.getFeatures();s.addInteraction(l),l.on("select",function(e){i.$emit("select",i.parseSelectedFeatures(r))});var c=new ol.interaction.DragBox({condition:ol.events.condition.platformModifierKeyOnly});s.addInteraction(c),c.on("boxend",function(){r.clear(),i.source.forEachFeatureIntersectingExtent(c.getGeometry().getExtent(),function(e){r.push(e)}),i.$emit("select",i.parseSelectedFeatures(r))})}t.$on("sidebar.toggle",function(){s.updateSize()}),this.$nextTick(function(){s.updateSize()})}}),biigle.$component("geo.components.sidebar",{template:'<aside class="sidebar" :class="classObject"><div class="sidebar__buttons"><sidebar-button v-for="tab in tabs" :tab="tab"></sidebar-button></div><div class="sidebar__tabs"><slot name="tabs"></slot></div></aside>',components:{sidebarButton:biigle.$require("geo.components.sidebarButton")},data:function(){return{open:!1}},props:{openTab:{type:String}},computed:{classObject:function(){return{"sidebar--open":this.open}},tabs:function(){for(var e=[],t=this.$slots.tabs.length-1;t>=0;t--)e.unshift(this.$slots.tabs[t].componentOptions.propsData);return e}},created:function(){this.$on("open",function(){this.open=!0,this.$emit("toggle")}),this.$on("close",function(){this.open=!1,this.$emit("toggle")})},mounted:function(){this.openTab&&this.$emit("open",this.openTab)}}),biigle.$component("geo.components.sidebarButton",{template:'<button class="sidebar__button btn btn-default btn-lg" :class="classObject" @click="toggle" :title="tab.title"><span v-if="open" class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span><span v-else class="glyphicon" :class="iconClass" aria-hidden="true"></span></button>',data:function(){return{open:!1}},props:{tab:{type:Object,required:!0}},computed:{iconClass:function(){return"glyphicon-"+this.tab.icon},classObject:function(){return{active:this.open}}},methods:{toggle:function(){this.open?this.$parent.$emit("close"):this.$parent.$emit("open",this.tab.name)}},mounted:function(){var e=this;this.$parent.$on("open",function(t){e.open=t===e.tab.name}),this.$parent.$on("close",function(){e.open=!1})}}),biigle.$component("geo.components.sidebarTab",{template:'<div class="sidebar__tab" :class="classObject"><slot></slot></div>',data:function(){return{open:!1}},props:{name:{type:String,required:!0},icon:{type:String,required:!0},title:{type:String}},computed:{classObject:function(){return{"sidebar__tab--open":this.open}}},created:function(){var e=this;this.$parent.$on("open",function(t){e.open=t===e.name}),this.$parent.$on("close",function(){e.open=!1})}}),biigle.$declare("geo.ol.style",function(){var e={colors:{blue:"#0099ff",white:"#ffffff",orange:"#ff5e00"},radius:{default:6},strokeWidth:{default:2}};return ol&&(e.default=new ol.style.Style({image:new ol.style.Circle({radius:e.radius.default,fill:new ol.style.Fill({color:e.colors.blue}),stroke:new ol.style.Stroke({color:e.colors.white,width:e.strokeWidth.default})})}),e.selected=new ol.style.Style({image:new ol.style.Circle({radius:e.radius.default,fill:new ol.style.Fill({color:e.colors.orange}),stroke:new ol.style.Stroke({color:e.colors.white,width:e.strokeWidth.default})}),zIndex:1/0})),e});