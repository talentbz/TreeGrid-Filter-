{
Cfg: { 
   CfgId:"GanttBasic", //  Grid identification for saving configuration to cookies
   NumberId:"1", IdChars:"0123456789", // Controls generating of new row ids
   ReloadChanged:'3' // Asks when reloading and there are pending changes
   },
Actions: { 
   OnRightClickCell:'Grid.Component.showCustomMenu(Row,Col)' // Custom event handler, shows the calling method of the framework component; Shows some custom popup menu on right click to any cell
   }, 
Panel: { Copy:"7" }, // Shows Add/Copy icon on left side panel
LeftCols: [
   { Name:"id", Width:"50", Type:"Int" }, // Row id, informational column
   { Name:"T", Width:"100", Type:"Text" }, // Column Task / Section-->
   { Name:"S", Width:"90", Type:"Date", Format:"MMM dd" }, // Column Start date-->
   { Name:"E", Width:"90", Type:"Date", Format:"MMM dd" }, // Column End Date
   { Name:"C", Width:"70", Type:"Int", Format:"##\\%;;0\\%" }, // Column Complete
   { Name:"D", Width:"80", Type:"Text", CanEdit:"0", Button:"Defaults", Defaults:"|*RowsColid*VariableDef", Range:"1" } // Column dependencies (descendants)
   ],
Cols: [
   { // Gantt chart column
      Name:"G", Type:"Gantt",
      GanttStart:"S", GanttEnd:"E", GanttComplete:"C", GanttDescendants:"D", // Defines the source columns for the Gantt chart
      GanttLastUnit:"d",                                                     // The end date is the last day
      GanttUnits:"d", GanttWidth:"18", GanttChartRound:"w", GanttRight:"1",  // Defines the Gantt zoom
      GanttHeader1:"w#dddddd MMMM yyyy", GanttHeader2:"d#ddddd", // Defines Gantt header for the zoom
      GanttBackground:"w#1/6/2008~1/6/2008 0:01", // Visualy separates the weeks by vertical line
      GanttEdit:"MainMove,MainResize,MainNew,MainComplete,Dependency" // Only the tasks and dependencies can be modified, except the task state
      }
   ],
Header: { id:"id", T:"Task", S:"Start", E:"End", C:"Comp\nlete", G:"Gantt", D:"Next" }, // Column captions

// Shows count of incorrect dependencies and on click corrects them
Toolbar: { Formula:"ganttdependencyerrors(null,1)", FormulaOnClick:"CorrectAllDependencies", FormulaTip:"Click to correct the dependencies" }
}