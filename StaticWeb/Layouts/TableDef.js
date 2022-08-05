{
Cfg: { // Cfg tag is splitted by attributes just for comments, you should merge them in your standard applications 
   CfgId:"Table", // Grid identification for saving configuration to cookies 
   Paging:'2', ChildPaging:'2', // Both paging set to client
   PageLength:'21', // count of rows at one page
   SaveSession:'1', // Stores IO Session to cookies to identify the client on server and access appropriate grid instance
   ShowDeleted:'0', // This example hides deleted row instead of coloring them red
   MaxHeight:'1', MinTagHeight:'400', // Grid maximizes height of the main tag on page
   IdChars:'0123456789', NumberId:'1', // row ids are set by numbers
   Sort:'Project,Resource', // To sort grid according to Project and Resource for first time (when no configuration saved)
   GroupMain:'Project', // Shows grouping tree in column Project
   Dragging:'0', // In this example is dragging not permitted
   UsePrefix:'1',// Uses prefix (GS,GL,GO,GM,GB,GP,GR) for custom class names to support all style
   Alternate:'3', // Custom style setting, every third row will have different color
   ReloadChanged:'3' // Asks when reloading and there are pending changes
   },
Actions: { 
   OnRightClickCell:'Grid.Component.showCustomMenu(Row,Col)' // Custom event handler, shows the calling method of the framework component; Shows some custom popup menu on right click to any cell
   }, 
Cols: [
   { Name:'id', Width:'50', Type:'Int' },
   { Name:'Project', Width:'250', Type:'Text' },
   { Name:'Resource', Width:'130', Type:'Text' },
   { Name:'Week', Width:'70', Type:'Int' },
   { Name:'Hours', Width:'70', Type:'Float', Format:'0.###' }
   ],
Def: {
   Group: {  // Default row for grouping, calculates summary for its group
      ProjectVisible:'0', ResourceVisible:'0', AggChildren:'1', Calculated:'1', 
      WeekFormula:'min()+"-"+max()', WeekType:'Text', WeekClassInner:'Number', HoursFormula:'sum()' 
      }
   },      
Header: { id:'id' },
Head: [
   { Kind:'Filter', idVisible:'0' }, // Filter row
   { Kind:'Group', Space:'1', Panel:'1', // Grouping row
      Cells:'List,Custom',
      List:'|Group by none|Group by Project|Group by Resource|Group by Project -> Resource',
      ListCustom:'Custom grouping',
      ListWidth:'180',
      Cols:'||Project|Resource|Project,Resource'
      }
   ],
Foot: [
   { CanEdit:'0', Project:'Summary', idVisible:'0', // Calculated summary row
     Calculated:'1', WeekFormula:'min()+"-"+max()', WeekType:'Text', WeekClassInner:'Number', HoursFormula:'sum()', HoursFormat:'0.##'
     }
   ],
Pager: { Width:'200' } // Right side pager
}