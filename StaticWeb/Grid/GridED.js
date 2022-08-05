// Include this script GridED.js instead of GridE.js to load the TreeGrid main script GridE.js on demand only, when some TreeGrid is being created
if(!window.TGLoadGridE){
   TGDefNames = {TGTreeGrid:1,TGPrintTreeGrid:1,TGStartTreeGrid:1,TGTCalc:1,TGGrids:1,TGSetEvent:1,TGAddEvent:1,TGDelEvent:1,TGGetEvent:1,TGGetGrids:1,TGAddGanttUnits:1};
   TGTreeGrid = function(Source,tag,param){
      var G = new function(){}(); G.Source = Source; G.MainTag = !tag ? null : typeof(tag)=="string" ? document.getElementById(tag) : tag;
      G.id = Source&&Source.id ? Source.id : param&&param.id?param.id : null;
      TGLoadGridE(null,function(){ TGTreeGrid(Source,tag,param,G); } );
      return G;
      }
   if(!window.TreeGrid) { TreeGrid = TGTreeGrid; TGDefNames.TreeGrid = 1; }
   TGPrintTreeGrid = function(Source,tag,param,page){
      var G = new function(){}(); G.Source = Source; G.MainTag = !tag ? null : typeof(tag)=="string" ? document.getElementById(tag) : tag;
      G.id = Source&&Source.id ? Source.id : param&&param.id?param.id : null;
      TGLoadGridE(null,function(){ TGPrintTreeGrid(Source,tag,param,page,G); } );
      return G;
      }
   if(!window.PrintTreeGrid) { PrintTreeGrid = TGPrintTreeGrid; TGDefNames.PrintTreeGrid = 1; }
   TGStartTreeGrid = function(){ TGLoadGridE(null,TGStartTreeGrid ); }
   if(!window.StartTreeGrid) { StartTreeGrid = TGStartTreeGrid; TGDefNames.StartTreeGrid = 1; }

   TGSetEvent = function(name,id,func,ident){ if(!func||!name) return 0; TGLoadGridE(null,function(){ TGSetEvent(name,id,func,ident); } ); return 1; }
   if(!window.SetEvent) { SetEvent = TGSetEvent; TGDefNames.SetEvent = 1; }
   TGAddEvent = function(name,id,func,ident){ if(!func||!name) return 0; TGLoadGridE(null,function(){ TGAddEvent(name,id,func,ident); } ); return 1; }
   if(!window.AddEvent) { AddEvent = TGAddEvent; TGDefNames.AddEvent = 1; }
   TGDelEvent = function(){ return false; }
   if(!window.DelEvent) { DelEvent = TGDelEvent; TGDefNames.DelEvent = 1; }
   TGGetEvent = function(){ return null; }

   TGGetGrids = function(){ return []; }
   if(!window.GetGrids) { GetGrids = TGGetGrids; TGDefNames.GetGrids = 1; }

   TGAddGanttUnits = function(units,width,exact){ TGLoadGridE(null,function(){ TGAddGanttUnits(units,width,exact); } ); }
   if(!window.AddGanttUnits) { AddGanttUnits = TGAddGanttUnits; TGDefNames.AddGanttUnits = 1; }

   if(!window.TGGrids){ var TGGrids = []; TGGrids.OnDemand = true; if(!window.Grids) { Grids = TGGrids; TGDefNames.Grids = 1; } }
   if(!window.TGTCalc){ var TGTCalc = function(){ }; TGTCalc.OnDemand = true; if(!window.TCalc) { TCalc = TGTCalc; TGDefNames.TCalc = 1; } }

   TGLoadGridE = function(path,func){
      if(TGLoadGridE.Loaded) { if(func) func(); return; }
      if(path&&typeof(path)=="function"){ func = path; path = null; }
      if(TGLoadGridE.Func){ TGLoadGridE.Func.push(func); return; }
      try {
         if(!path) {
            var S = document.getElementsByTagName("script");
            for(var i=0;i<S.length;i++) {
               if(S[i].src && S[i].src.indexOf("GridED.js")>=0){ path = S[i].src.replace("GridED.js","GridE.js"); break; }
               if(S[i].src && S[i].src.indexOf("GridE.js")>=0){ path = S[i].src.replace("GridE.js","GridED.js"); break; }
               }
            if(!path){ alert("Cannot download TreeGrid script!\r\n\r\nThe script path is empty"); return; }
            }
         TGLoadGridE.Func = func?[func]:[];
         var script = document.createElement("script");
         script.type = "text/javascript";
         script.src = path;
         script.crossOrigin = true;
         document.documentElement.getElementsByTagName("head")[0].appendChild(script);
         TGLoadGridE.Finish = function(){
            if(window.TGComponent) { TGLoadGridE.Loaded = true; for(var i=0;i<TGLoadGridE.Func.length;i++) TGLoadGridE.Func[i](); }
            else setTimeout(TGLoadGridE.Finish,100);
            }
         TGLoadGridE.Finish();
         }
      catch(e){ alert("Cannot download TreeGrid script!\r\n\r\nError message:\r\n"+(e.message ? e.message:e)); }
      if(!window.LoadGridE) LoadGridE = TGLoadGridE;
      }

   TGLoadGridE.TG = function(){
      var A = document.getElementsByTagName("treegrid"); if(A.length) { TGStartTreeGrid(); return; }
      A = document.getElementsByTagName("bdo"); if(A.length) for(var i=0;i<A.length;i++) if(!A[i].dir) { TGStartTreeGrid(); return; }
      }
   if(document.readyState=="complete") TGLoadGridE.TG();
   else if(window.addEventListener) { window.addEventListener("load",TGLoadGridE.TG,false); window.addEventListener("DOMContentLoaded",TGLoadGridE.TG,false); }
   else if(window.attachEvent) window.attachEvent("onload",TGLoadGridE.TG);
   }