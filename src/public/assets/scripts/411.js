"use strict";(self.webpackChunkbiigle_geo=self.webpackChunkbiigle_geo||[]).push([[411],{704:(e,t,r)=>{function n(e,t){let r=e.length-t,n=0;do{for(let r=t;r>0;r--)e[n+t]+=e[n],n++;r-=t}while(r>0)}function i(e,t,r){let n=0,i=e.length;const o=i/r;for(;i>t;){for(let r=t;r>0;--r)e[n+t]+=e[n],++n;i-=t}const l=e.slice();for(let t=0;t<o;++t)for(let n=0;n<r;++n)e[r*t+n]=l[(r-n-1)*o+t]}r.d(t,{Z:()=>o});class o{async decode(e,t){const r=await this.decodeBlock(t),o=e.Predictor||1;if(1!==o){const t=!e.StripOffsets;return function(e,t,r,o,l,s){if(!t||1===t)return e;for(let e=0;e<l.length;++e){if(l[e]%8!=0)throw new Error("When decoding with predictor, only multiple of 8 bits are supported.");if(l[e]!==l[0])throw new Error("When decoding with predictor, all samples must have the same size.")}const a=l[0]/8,c=2===s?1:l.length;for(let s=0;s<o&&!(s*c*r*a>=e.byteLength);++s){let o;if(2===t){switch(l[0]){case 8:o=new Uint8Array(e,s*c*r*a,c*r*a);break;case 16:o=new Uint16Array(e,s*c*r*a,c*r*a/2);break;case 32:o=new Uint32Array(e,s*c*r*a,c*r*a/4);break;default:throw new Error(`Predictor 2 not allowed with ${l[0]} bits per sample.`)}n(o,c)}else 3===t&&(o=new Uint8Array(e,s*c*r*a,c*r*a),i(o,c,a))}return e}(r,o,t?e.TileWidth:e.ImageWidth,t?e.TileLength:e.RowsPerStrip||e.ImageLength,e.BitsPerSample,e.PlanarConfiguration)}return r}}},411:(e,t,r)=>{r.r(t),r.d(t,{default:()=>i});var n=r(704);class i extends n.Z{decodeBlock(e){const t=new DataView(e),r=[];for(let n=0;n<e.byteLength;++n){let e=t.getInt8(n);if(e<0){const i=t.getUint8(n+1);e=-e;for(let t=0;t<=e;++t)r.push(i);n+=1}else{for(let i=0;i<=e;++i)r.push(t.getUint8(n+i+1));n+=e+1}}return new Uint8Array(r).buffer}}}}]);