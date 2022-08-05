{
Cfg: { 
   CfgId:"GanttTree", //  Grid identification for saving configuration to cookies
   Group:"L", GroupMain:"T", Sort:"T", // Groups by L column and displays tree in column T (Task / Subtask)
   NumberId:"1", IdChars:"0123456789", // Controls generation of new row ids
   ScrollLeftLap:"0", // Saves horizontal scroll in Gantt to cookies
   GroupMoveFree:"2", // Rows can be moved also as children to data rows, set it width DefParent and DefEmpty
   MaxHeight:"1", MinTagHeight:"300", // Maximizes height of the main tag
   ReloadChanged:'3' // Asks when reloading and there are pending changes
   },
Actions: { 
   OnRightClickCell:'Grid.Component.showCustomMenu(Row,Col)' // Custom event handler, shows the calling method of the framework component; Shows some custom popup menu on right click to any cell
   }, 
Def: {
   Task: {
      Group:"1", // The Default "Task" will be used for grouping
      Expanded:"1", Calculated:"1", CalcOrder:"S,E,C,G", // Group of task calculates summary of the tasks
      SFormula:"minimum(min('S'),min('E'))", // Gets the first start date from its children
      EFormula:"maximum(max('S'),max('E'))", // Gets the last end date from its children
      CFormula:"ganttpercent('S','E','d')", // Calculates average task completion from its children
      GColor:"240,240,240", // Changes background color
      DButton:"", // Cannot change dependency of group task
      GGanttClass:"Group", GGanttIcons:"1", GGanttEdit:"0", GGanttHover:"0", // Gantt setting specific for Group rows, changes colors and restrict changes by a user
      NoUpload:"1", // Does not upload this row to the server
      },
   R: {
      DefParent:"Task", // Changes the parent row to group row when it gets its first child
      DefEmpty:"R" // Changes the parent row to data row when it looses its last child
      }
   },
Panel: { Copy:"7" }, // Shows Add/Copy icon on left side panel
LeftCols: [
   { Name:"id", Width:"40", Type:"Text", CanEdit:"0" }, // Row id, informational column
   { Name:"T", Width:"170", Type:"Text" }, // Column Task / Section-->
   { Name:"S", Width:"90", Type:"Date", Format:"MMM dd" }, // Column Start date-->
   { Name:"E", Width:"90", Type:"Date", Format:"MMM dd" }, // Column End Date
   { Name:"C", Width:"70", Type:"Int", Format:"##\\%;;0\\%" }, // Column Complete
   { Name:"D", Width:"70", Type:"Text", CanEdit:"0", Button:"Defaults", Defaults:"|*RowsColid*VariableDef", Range:"1" } // Column dependencies (Next)
   ],
Cols: [
   { // Grouping levels definitions
      Name:"L", Width:"120", CanEdit:"0", 
      CanGroup:"2", // Does not hide the column when is grouped by
      GroupChar:"/", // The individual grouping level names are separated by '/'
      GroupEmpty:"0", // Does not create groups for empty Levels
      },

  // Gantt chart column, basic settings
  { 
      Name:"G", Type:"Gantt", // Defines the Gantt column
      GanttStart:"S", GanttEnd:"E", GanttComplete:"C", GanttDescendants:"D", // Defines the source columns for Gantt
      GanttUnits:"d", GanttChartRound:"w", GanttRight:"1", // Defines the zoom settings
      GanttHeader1:"w#dddddd MMMM yyyy", GanttHeader2:"d#ddddd", GanttBackground:"w#1/6/2008~1/6/2008 0:01", // Defines headers and background for the zoom
      GanttEdit:"Main,Dependency",  // Only main bar and dependency can be edited
      GanttSlack:"1" // Calculates critical path
      }
   ],
Header: { id:"ID", T:"Task", S:"Start", E:"End", C:"Com\nplete", G:"Gantt", D:"Next", L:"Tree Levels" }, // Column captions
   
// Shows count of incorrect dependencies and on click corrects them
Toolbar: { Formula:"ganttdependencyerrors(null,1)", FormulaOnClick:"CorrectAllDependencies", FormulaTip:"Click to correct the dependencies" }

}