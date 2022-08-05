TreeGridLoaded ({ /* JSONP header, to be possible to load from xxx_Jsonp data source */ 
   "Cfg" : { "id": "Sheet", "SuppressCfg": "1", "Sorting": "0"   },  // Suppresses configuration and sorting 
   "Cfg_2" : { "SuppressMessage": "3"   },  // Suppresses all messages, including page messages 
   "Cfg_3" : { "AutoIdPrefix": ""   },  // Prefix for automatically generated row id, here set empty instead of AR 
   "Cfg_4" : { "LimitScroll": "5"   },   // Responsive design, for small windows sets NoVScroll. Does not set NoHScroll, because ResizingMain vertically is permitted 
   "Cfg_5" : { "Language": "EN"   },  // Presets English language and shows the Languages combo 
   "Cfg_6" : { "PrintVarHeight": "1"   },  // Calculates height of not rendered rows 
   "Cfg_7" : { "PrintPagePrefix": "<center class='%9' style='width:%7px'>Sheet example printed page %3 from %6</center>"   },  // Sample page header for printing 
   "Cfg_8" : { "PrintPagePostfix": "<center class='%9' style='width:%7px'>Page %1 horizontally from %4 , page %2 vertically from %5</center>"   },   // Sample page footer for printing 
   "Cfg_9" : { "ResizingMain": "1"   },  // Permits resizing main tag vertically by dragging top right corner 

   // Automatic column and row pages    
   "Cfg_10" : { "Paging": "2", "AutoPages": "1", "PageLength": "10", "MaxPages": "3", "RemoveUnusedFixed": "0", "RemoveAutoPages": "1"   },  // Defines automatic row pages 
   "Pager" : { "Visible": "0"   },  // Hides the side pager that is shown for paging by default 
   "Cfg_11" : { "ColPaging": "2", "AutoColPages": "1", "ColPageLength": "10", "ColPageMin": "0", "MaxColPages": "3", "ColPagingFixed": "1", "RemoveAutoColPages": "1"   },  // Defines automatic column pages 

   // Defines row and column indexes 
   "Cfg_12" : { "RowIndex": "Index", "RowIndexType": "46"   },  // Creates number RowIndex with all data rows except deleted 
   "Cfg_13" : { "ColIndex": "Header", "ColIndexType": "30", "ColIndexChars": "ABCDEFGHIJKLMNOPQRSTUVWXYZ"   },  // Creates letter ColIndex with all data columns except deleted  

   // Permits manipulation 
   "Cfg_14" : { "ColAdding": "1"   },  // Permits adding new columns. Adding and copying rows and copying columns is permitted by default 
   "Cfg_15" : { "ColDeleting": "1"   },  // Permits deleting columns. Deleting rows is permitted by default 
   "Cfg_16" : { "SelectingCols": "1"   },  // Permits selecting the whole columns. Selecting rows is permitted by default 
   "Cfg_17" : { "SelectAllType": "31"   },  // Makes the SelectAll button on panels a switch; it is changed also when the row/column is selected/unselected; the added rows/columns are automatically selected if it is on 

   // Cell popup menu to manipulate rows and columns 
   "Cfg_18" : { "Menu": {Head:'Actions',Items:[
         {Name:'Rows',Menu:1,Items:'|SelectRow|DeselectRow|SelectFocusedRows@2|DeselectFocusedRows@2|-|SelectAll|DeselectAll|InvertAll|-|ShowRowAbove|ShowRowBelow|ShowRows|ShowRowsW|-|ShowHeader|ShowPanelRow|ShowNames|ShowToolbar|-|HideRowsA|HideRowsSR|-|HideHeader|HidePanelRow|HideNames|HideToolbar|-|AddRow|AddRowsSR@2|-|CopyRow|CopySelectedR|-|DeleteRow|UndeleteRow|DeleteRowsSR|UndeleteRowsSR|-|RemoveRow|RemoveRowsSR|-|FixAbove'},
         {Name:'Columns',Menu:1,Items:'|SelectCol|DeselectCol|SelectFocusedCols@2|DeselectFocusedCols@2|-|SelectAllCols|DeselectAllCols|-|ShowColLeft|ShowColRight|ShowCols|ShowColsW|-|ShowIndex|ShowPanel|ShowIds|-|HideColsA|HideColsSC|-|HideIndex|HidePanel|HideIds|-|AddCol|AddColsSR@2|-|CopyCol|CopySelectedColsC|-|DeleteCol|UndeleteCol|DeleteColsSC|UndeleteColsSC|-|RemoveCol|RemoveColsSC|-|FixPrev'},
         {Name:'Edit',Menu:1,Items:'|SetEditable|SetReadOnly|SetPreview|ClearEditable|-|ShowFormula|HideFormula|DefaultFormula|-|Lock0|Lock1|Lock2|Lock3'},
         {Name:'Clear',Menu:1,Items:'|ClearValueStyle|ClearValue|ClearStyle'},
         {Name:'Format',Menu:1,Items:'|ChooseFormat|SetFormat'},
         {Name:'Span',Menu:1,Items:'|Span|Split'},
         {Name:'Border',Menu:1,Items:'|SetBorder|ClearBorder|-|ChooseBorderStyle|ChooseBorderEdge|ChooseBorderColor'},
         {Name:'Style',Menu:1,Items:'|BoldOn|BoldOff|ItalicOn|ItalicOff|UnderlineOn|UnderlineOff|StrikeOn|StrikeOff|OverlineOn|OverlineOff|-|SetTextLine|ChooseTextLine|NoTextLine|-|SmallCapsOn|SmallCapsOff|Superscript|Subscript|Noscript|-|NoTextStyle'},
         {Name:'Font',Menu:1,Items:'|SetTextFont|NoTextFont|ChooseTextFont|-|SetTextSize|NoTextSize|ChooseTextSize|-|IncreaseTextSize|DecreaseTextSize'},
         {Name:'Color',Menu:1,Items:'|SetTextColor|NoTextColor|ChooseTextColor|-|SetTextShadow|NoTextShadow|ChooseTextShadow|-|SetTextShadowColor|NoTextShadowColor|ChooseTextShadowColor'},
         {Name:'Background',Menu:1,Items:'|SetColor|NoColor|ChooseColor|-|SetPattern|ChoosePattern|NoPattern|-|SetPatternColor|ChoosePatternColor|NoPatternColor'},
         {Name:'Align',Menu:1,Items:'|AlignLeft|AlignRight|AlignCenter|NoAlign|-|VertAlignTop|VertAlignBottom|VertAlignMiddle|NoVertAlign'},
         {Name:'Direction',Menu:1,Items:'|RotateLeft|RotateLeft30|RotateLeft45|RotateLeft60|RotateLeft90|RotateLeftVert|RotateRight|RotateRight30|RotateRight45|RotateRight60|RotateRight90|RotateRightVert|NoRotate|-|WrapOn|WrapOff|NoWrap'},
         {Name:'Text',Menu:1,Items:'|ShowCalendar|-|UpperCase|LowerCase|-|ShowLink|SetLink|ClearLink'},
         {Name:'Image',Menu:1,Items:'|OpenImage|-|AssignImage|RestoreImage|RestoreImageSize|RestoreImagePosition|RestoreImageRotation|EnterImageOpacity|EnterImageName|-|FloatImage|TextImage|DuplicateImage|DeleteImage|-|DragImageMove|DragImageCopy|DragImageResize|DragImageRotate|DragImageNone|DragImageClear'},
         ]}   }, 
   "Lang" : { 
      "MenuCell" : { 
         "ShowHeader": "Show header", "HideHeader": "Hide header", 
         "ShowPanelRow": "Show panel", "HidePanelRow": "Hide panel", 
         "ShowNames": "Show names", "HideNames": "Hide names", 
         "ShowToolbar": "Show toolbar", "HideToolbar": "Hide toolbar", 
         "ShowIndex": "Show index", "HideIndex": "Hide index", 
         "ShowPanel": "Show panel", "HidePanel": "Hide panel", 
         "ShowIds": "Show ids", "HideIds": "Hide ids"  
          }  
   }, 
   "Cfg_19" : { "ShowMenuSingle": "1"   },  // Shows menu also with single option instead of doing it immediately 
   "Cfg_20" : { "HideMenuUnused": "1"   },  // Hide unused child items, disable parent items with no child items 
   "Cfg_21" : { "MaxMenuCells": "1"   },  // Searches only for the first editable cell to permit the menu cell action, to speed up showing the menu 
   "Actions" : { "OnRightClick": "Grid.IsSelected(Row,Col) OR Focus, ShowPopupMenu OR ShowNoMenu", "OnLongClick1": "Grid.IsSelected(Row,Col) OR Focus, ShowPopupMenu OR ShowNoMenu"   },  // Shows the popup menu for any right click to the grid 

   //- Formula editing 
   "Cfg_22" : { "FormulaEditing": "1"   },  // Permits formula editing; Uploads the formula in the cell value, not in EFormula attribute 
   "Cfg_23" : { "FormulaChanges": "0"   },  // Does not mark values calculated by formula as Changed 
   "Cfg_24" : { "FormulaType": "56"   },  // Does not calculate deleted, filtered and hidden rows and columns, but calculates fixed data rows and columns 
   "Cfg_25" : { "DragEdit": "2"   },  // Permits dragging during editing to choose cell ranges in formula 
   "Cfg_26" : { "FormulaRelative": "1"   },  // Cell references can be absolute or relative. Cell references in formulas in data xml in/out are in standard notation 
   "Cfg_27" : { "FormulaLocal": "0"   },  // Formula names in xml in/out are not localized English format 
   "Cfg_28" : { "FormulaResults": "31"   },  // Cells with error formulas are marked red and error message is shown; the formula results are checked against cell restrictions; the null and NaN results are not permitted  
   "Cfg_29" : { "FormulaCircular": "6"   },  // Circular cell references in formulas are restricted with error message 
   "Cfg_30" : { "EditErrorsMessageTime": "1000"   },  // How long the formula and edit error message will be shown 
   "Cfg_31" : { "FormulaAddParenthesis": "1"   },  // Tries to adds ')' to the end of formula when editing resulted to incorrect formula 
   "DefCols" : [  { "Name": "Auto", "FormulaSuggest": "6"   }   ],   // For every column generates suggest list of all available formula functions to use in formula editing 
   "Actions_2" : { "OnDragHeader": "ChooseColsReplaceAll OR ColMoveSelected OR ColMove", "OnCtrlDragHeader": "ChooseColsInsert OR ColCopySelected OR ColCopy"   },  // Dragging header during formula edit will choose the columns to the range 

   // Other settings often set in the sheets 
   "Cfg_32" : { "Undo": "79"   },  // Permits undoing all actions, including scroll 
   "Cfg_33" : { "EnterMode": "1"   },  // Enter moves cursor down 
   "Cfg_34" : { "FocusRect": "31"   },  // Permits focusing cell range, shows the corner, hides focused cursor for the whole row, shows relative color for focused cell, permits move and copy the focused range by dragging. 
   "Cfg_35" : { "SelectingCells": "3"   },  // Permits selecting cells and rows/columns independently 
   "Cfg_36" : { "SelectingFocus": "1"   },  // Automatically selects the focused cells and clears all other selected cells on focus change 
   "Cfg_37" : { "AutoFillType": "31"   },   // Permits auto filling numbers and strings, also from one cell, shrinking range clears the rest 
   "Actions_3" : { "OnDel": "ClearSelectedCellsF OR ClearCellF"   },  // Clears the selected cells 
   "MenuPrint" : { "HideUnused": "2", "Items": "ColsCaption,Cols,Head,Foot,OptionsCaption,Options,SizeCaption,Size"   },  // Hides all columns and rows from print menu, because they are always exported 
   "Lang_2" : {  "MenuColumns" : { "ColsCaption": "Print headers"   }   },  // Renames the Choose columns caption, because there is only one column and one row 
   "MenuExport" : { "HideUnused": "2"   },  // Hides all columns and rows from export menu, because they are always exported 

   // Dynamic format 
   "Cfg_38" : { "DynamicFormat": "1"   },  // Permits changing cell format dynamically by users 
   "Cfg_39" : { "AutoCalendar": "1"   },  // Does not show date button for Date type, but shows calendar when editing date cell 

   // Dynamic style 
   "Cfg_40" : { "DynamicStyle": "1"   },  // Permits to set and change the style attributes for individual cells 
   "Cfg_41" : { "LineHeightRatio": "1.25"   },  // Ratio line-height vs font-size 

   // Dynamic editing 
   "Cfg_42" : { "DynamicEditing": "1"   },      // Permits changing editing permissions 
   "Cfg_43" : { "AutoHtml": "1"   },            // Permits rich edit including images 

   // Settings for cell span, borders and mass changes 
   "Cfg_44" : { "EditAttrs": ",EFormula,Format,EditFormat,CanEdit,Span,RowSpan,BorderTop,BorderRight,BorderBottom,BorderLeft,Wrap,Align,VertAlign,Rotate,Color,Pattern,PatternColor,TextColor,TextStyle,TextSize,TextFont,TextShadow,TextShadowColor"   },  // What will be affected by mass change like clear or move focus; the first empty item means value 
   "Cfg_45" : { "DynamicSpan": "2"   },  // Permits dynamic spanning and splitting spanned cells 
   "Cfg_46" : { "DynamicBorder": "1"   },  // Permits dynamic change of cell borders, only for variable rows and middle columns 
   "Cfg_47" : { "SpannedBorder": "3"   },  // Update border in spanned cells to better displayed 
   "Cfg_48" : { "BorderType": "0"   },  // Set borders only in visible, not deleted cells 
   "Cfg_49" : { "SelectHidden": "0"   },  // Select only visible, not deleted cells 
   "Cfg_50" : { "MoveFocusType": "11"   },  // Ignore span in cells when moving focused range by dragging 

   // Settings for copying and pasting cells via clipboard 
   "Cfg_51" : { "CopyCols": "0"   },  // Copy only focused cells 
   "Cfg_52" : { "ExcludeClear": "1"   },  // CtrlX clears the copied cells 
   "Cfg_53" : { "PasteCols": "5"   },  // Pastes to focused cell range or to focused and next columns
   "Cfg_54" : { "PasteFocused": "11"   },  // Pastes to focused cell range or to focused and next rows 

   // Default width and other settings of all column 
   "DefCols_2" : [ 
      { "Name": "Auto", "Type": "Auto"   },                  // Default type of all data cells in grid is set to auto  
      { "Name": "Auto", "Width": "90"   },                   // Default width of all columns is 90 pixels 
      { "Name": "Auto", "CanPrint": "1"   },                 // Prints the column if visible 
      { "Name": "Auto", "CanExport": "2"   },                // Exports the column always, even if hidden, and does not show it in export menu 
      { "Name": "Auto", "MenuName":"" },                     // Does not display auto columns in column menus
      { "Name": "Auto", "VarHeight": "1", "VarHeightType": "7"   }   // When printing, all cells except empty, numbers and simple texts will be checked for their height 
   ], 

   // The fixed rows and column - panels, indexes and ids 
   "Head" : [ 
      { "Kind":"Header", "id": "Header", "Index": "Index", "Align": "Center", "idVisible": "0", "CanHide": "1", "CanExport": "0", "MenuName": "Column indexes (top)"   },  // Centers all cells in header 
      { "Kind":"Panel", "id": "Panel", "Panel": "Panel", "PanelType": "Text", "idVisible": "0"   },  // Adds panel for columns 
      { "Kind":"Panel", "id": "Panel", "Index": "ColSelectAll,ColDeleteAll,ColCopyAll"   },  // Defines group column actions for the left index panel 
      { "Kind":"Panel", "id": "Panel", "OnClickPanelColDelete": "ShowMenu OR ShowNoMenu", "PanelColDeleteMenu": "|ShowColLeft|ShowColRight|HideColsA|-|DeleteCol|UndeleteCol|-|RemoveCol"   },  // Defines menu for the column delete button 
      { "Kind":"Panel", "id": "Panel", "OnClickPanelColDeleteAll": "ShowMenu OR ShowNoMenu", "PanelColDeleteAllMenu": "|ShowColsS|ShowColsW|HideColsS|-|DeleteColsS|UndeleteColsS|-|RemoveColsS"   }   // Defines menu for the column delete all button 
   ], 

   // Bottom fixed row - ids 
   "Foot" : [ 
      { "id": "ID", "NoIndex": "1", "Index": " ", "PanelType": "Text", "Panel": "Name", "ShowColNames": "1", "CanFocus": "0", "CanExport": "0", "MenuName": "Column names (bottom)"   }   // Informational bottom row with column names 
   ], 

   // Left fixed columns - index and panel 
   "LeftCols" : [ 
      { "Name": "Index", "Def": "Index", "Width": "60", "Resizing": "1", "NoUpload": "1", "CanExport": "0", "MenuName": "Row indexes (left)"   },  // Defines with of the Index column and lets resizing rows by it 
      { "Name":"Panel", "Name": "Panel", "Copy": "1"   },  // Places the panel right side to Index and shows add/copy button on it 
      { "Name":"Panel", "Name": "Panel", "OnClickPanelDelete": "ShowMenu OR ShowNoMenu", "PanelDeleteMenu": "|ShowRowAbove|ShowRowBelow|HideRowsA|-|DeleteRow|UndeleteRow|-|RemoveRow"   },  // Defines menu for the row delete button 
      { "Name":"Panel", "Name": "Panel", "OnClickPanelDeleteAll": "ShowMenu OR ShowNoMenu", "PanelDeleteAllMenu": "|ShowRowsS|ShowRowsW|HideRowsS|-|DeleteRowsS|UndeleteRowsS|-|RemoveRowsS"   }   // Defines menu for the row delete all button 
   ], 

   // Right fixed columns - ids 
   "RightCols" : [ 
      { "Name": "id", "Width": "50", "CanFocus": "0", "CanSelect": "0", "CanDelete": "0", "MenuName": "Row ids (right)", "CanExport": "0", "Align": "Center", "FormulaCanUse": "0"   }   // Informational right column with row ids 
   ], 

   // Bottom toolbars 
   "Solid" : [ 

      // Main source toolbar, hidden to use the mirrors 
      { "Kind":"Toolbar", "id": "Toolbar", "Visible": "0", "CanHide": "0", "Space": "0"   },  // Moves the toolbar to top and hides it to use only its mirrors 

      // Two smaller toolbars displayed by default 
      { "Kind":"Toolbar", "id": "Toolbar1", "Kind": "Toolbar1", "Mirror": "Toolbar", "Cells30Manipulate": "Undo,Redo,Outdent,Indent,ExpandAll,CollapseAll", "Cells40Sheet": "", "Visible": "1", "CanHide": "1"   }, 
      { "Kind":"Toolbar", "id": "Toolbar2", "Kind": "Toolbar2", "Mirror": "Toolbar", "Cells20Data": "", "Cells30Manipulate": "Add,AddChild,AddCol,Join,Split", "Cells60Cfg": "", "Cells70Styles": "", "Visible": "1", "CanHide": "1"   }, 

      // Three smaller ones, shown in screens 590 - 830 
      { "Kind":"Toolbar", "id": "Toolbar3", "Kind": "Toolbar1", "Mirror": "Toolbar", "Cells40Sheet": "AddImage,SetLink,TextFormat", "Cells70Styles": "", "Visible": "0", "CanHide": "0"   }, 
      { "Kind":"Toolbar", "id": "Toolbar4", "Kind": "Toolbar2", "Mirror": "Toolbar", "Cells20Data": "", "Cells30Manipulate": "", "Cells40Sheet": "Left,Center,Right,TextIndent,Top,Middle,Bottom,Bold,Italic,Underline,Strike,RotateLeft,NoRotate,RotateRight,WrapText,ClearStyle,Size,ColorText,ColorShadow,ColorBackground,Border", "Cells60Cfg": "", "Cells70Styles": "", "Visible": "0", "CanHide": "0"   }, 
      { "Kind":"Toolbar", "id": "Toolbar5", "Kind": "Toolbar1", "Mirror": "Toolbar", "Cells20Data": "", "Cells30Manipulate": "", "Cells40Sheet": "", "Cells60Cfg": "", "Visible": "0", "CanHide": "0"   }, 

      // Five smaller ones, shown in screens less than 610 
      { "Kind":"Toolbar", "id": "Toolbar6", "Kind": "Toolbar1", "Mirror": "Toolbar", "Cells30Manipulate": "Undo,Redo,Add,AddChild,AddCol,Outdent,Indent,ExpandAll,CollapseAll", "Cells40Sheet": "", "Cells60Cfg": "", "Cells70Styles": "Languages", "Visible": "0", "CanHide": "0"   }, 
      { "Kind":"Toolbar", "id": "Toolbar7", "Kind": "Toolbar2", "Mirror": "Toolbar", "Cells20Data": "", "Cells30Manipulate": "", "Cells40Sheet": "AddImage,SetLink,Left,Center,Right,TextIndent,Top,Middle,Bottom,Bold,Italic,Underline,Strike", "Cells60Cfg": "", "Cells70Styles": "", "Visible": "0", "CanHide": "0"   }, 
      { "Kind":"Toolbar", "id": "Toolbar8", "Kind": "Toolbar1", "Mirror": "Toolbar", "Cells20Data": "", "Cells30Manipulate": "Join,Split", "Cells40Sheet": "RotateLeft,NoRotate,RotateRight,WrapText,ClearStyle,Size,ColorText,ColorShadow,ColorBackground,Border", "Cells60Cfg": "", "Cells70Styles": "", "Visible": "0", "CanHide": "0", "TextFormatWidth": "58"   }, 
      { "Kind":"Toolbar", "id": "Toolbar9", "Kind": "Toolbar2", "Mirror": "Toolbar", "Cells20Data": "", "Cells30Manipulate": "", "Cells40Sheet": "TextFormat", "Cells70Styles": "Styles,GanttStyles", "Visible": "0", "CanHide": "0"   }, 
      { "Kind":"Toolbar", "id": "Toolbar10", "Kind": "Toolbar2", "Mirror": "Toolbar", "Cells20Data": "", "Cells30Manipulate": "", "Cells40Sheet": "", "Cells60Cfg": "", "Cells70Styles": "Sizes,Scales,Contrasts", "Visible": "0", "CanHide": "0"   }  

   ], 

   "Media" : [ 

      // Reduces style size for smaller displays 
      { "MaxWidth": "1090", "Tag": "1", 
         "Cfg" : { "Size": "Low", "MainTagHeight": "520"   }  
      }, 
      { "MaxHeight": "800", "Tag": "0", 
         "Cfg" : { "Size": "Low", "MainTagHeight": "470"   }  
      }, 

      // Reduces style size for smaller displays 
      { "MaxWidth": "1010", "Tag": "1", 
         "Cfg" : { "Size": "Tiny", "MainTagHeight": "470"   }  
      }, 

      // Shows 3 toolbars instead of 2 for smaller displays 
      { "MaxWidth": "830", 
         "Rows" : [ 
            { "id": "Toolbar", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar1", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar2", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar3", "Visible": "1", "CanHide": "1"   }, 
            { "id": "Toolbar4", "Visible": "1", "CanHide": "1"   }, 
            { "id": "Toolbar5", "Visible": "1", "CanHide": "1"   }  
         ], 
      }, 

      // Shows 5 toolbars instead of 2 for smaller displays 
      { "MaxWidth": "610", 
         "Rows" : [ 
            { "id": "Toolbar", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar1", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar2", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar3", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar4", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar5", "Visible": "0", "CanHide": "0"   }, 
            { "id": "Toolbar6", "Visible": "1", "CanHide": "1"   }, 
            { "id": "Toolbar7", "Visible": "1", "CanHide": "1"   }, 
            { "id": "Toolbar8", "Visible": "1", "CanHide": "1"   }, 
            { "id": "Toolbar9", "Visible": "1", "CanHide": "1"   }, 
            { "id": "Toolbar10", "Visible": "1", "CanHide": "1"   }  
         ], 
      }, 

      // Updates the custom colors of bottom row with column names and right column with row ids for various styles 
      { "Style": "TB", 
         "Rows" : [ 
            { "id": "ID", "Color": "#292C33"   }  
         ], 
         "Cols" : [ 
            { "Name": "id", "Color": "#292C33"   }  
         ], 
      }, 
      { "Style": "TW", 
         "Rows" : [ 
            { "id": "ID", "Color": "#F4F4F4", "PanelColor": "#F4F4F4", "idColor": "#F4F4F4"   }  
         ], 
         "Cols" : [ 
            { "Name": "id", "Color": "#F4F4F4"   }  
         ], 
      }, 
      { "Style": "TM", 
         "Rows" : [ 
            { "id": "ID", "Color": "#2196F3", "TextColor": "White", "IndexColor": "#2196F3", "PanelColor": "#2196F3", "PanelTextColor": "White", "idColor": "#2196F3"   }  
         ], 
         "Cols" : [ 
            { "Name": "id", "Color": "#2196F3", "TextColor": "White"   }  
         ], 
      }, 
   ], 

   // Translations of example control texts to other languages 
   "Languages" : { 
      "L" : { "Code": "-EN", "Layout_Url": "SheetLang.js"   }  
   }, 

}) /* End of JSONP header */ 