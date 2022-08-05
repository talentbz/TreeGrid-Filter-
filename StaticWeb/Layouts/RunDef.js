{
Cfg: { CfgId:"Run", SuppressCfg:"1", Undo:"1", Dragging:"0", MaxHeight:"1", NumberId:"1", IdChars:"0123456789", ReloadChanged:'3' },
Actions: { 
   OnRightClickCell:'Grid.Component.showCustomMenu(Row,Col)' // Custom event handler, shows the calling method of the framework component; Shows some custom popup menu on right click to any cell
   }, 
LeftCols: [
   { Name:"T", Width:"70", Type:"Text" },  // Column Task / Section
   { Name:"S", Width:"90", Type:"Date", Format:"MMM dd" }, // Column Start date
   { Name:"R", Width:"100", Type:"Text", Range:"1" } // Column Run 
   ],
Cols: [
   { // Gantt chart column
      Name:"G", Type:"Gantt",
      GanttRunStart:"S", GanttRun:"R",
      GanttUnits:"d", GanttChartRound:"w", GanttEdit:"All", GanttDataUnits:"d",
      GanttRight:"1", 
      GanttBackground:"1/6/2008~1/6/2008 0:01", GanttBackgroundRepeat:"w",
      GanttHeader1:"w", GanttFormat1:"dddddd MMMM yyyy",
      GanttHeader2:"d", GanttFormat2:"ddddd", 
      GanttRunNewStart:"2,end,Start;;1,box;;2,end,End",
      GanttRunMove:"slide", GanttRunAdjustCopy:"resize,move,shrink,append", GanttRunAdjustSlide:"shrink",
      GanttRunMoveCtrl:"move", GanttRunAdjustMove:"shrink,move,append",
      GanttRunMoveShift:"move,single,all", GanttRunAdjustMoveShift:"error",
      GanttRunResize:"resize", GanttRunAdjustResize:"shrink",
      GanttRunResizeCtrl:"resize,all",
      GanttRunTypes:"Box,Box;Box 1,Box,,Fuchsia;Box 2,Box,,Aqua;Box 3,Box,,Lime;Box 4,Box,,Orange;Solid box,Solid,, ;Solid box 1,Solid,,Gray;Solid box 2,Solid,,Black;Fixed left box,Left,, ;Fixed right box,Right,, ;Fixed box,Fixed,, "
      }
   ],
Header: { id:"ID", T:"Task", R:"Run", S:"Start", G:"Gantt" } // Column captions 
}