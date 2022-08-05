{
Cfg : {
   CfgId:"Static", //  Grid identification for saving configuration to cookies
   PrintLoad:"1", PrintCols:"2", PrintLocation:"3", PrintPageBreaks:"1", PrintRows:"50", // Printing options, download all rows for printing
   Paging:'2', ChildPaging:'2', // Both paging set to server
   ChildPageLength:"20", // Server paging for child pages, splits children to given number of rows and loads them separately when they become visible due scroll
   SaveSession:'1', // Stores IO Session to cookies to identify the client on server and access appropriate grid instance
   Prepared:'1', // DLL sends data prepared, so you can set this attribute to speed up loading
   ShowDeleted:'0', // This example hides deleted row instead of coloring them red
   MaxHeight:'1', // Grid maximizes height of the main tag on page
   LimitScroll:"23", MinBodyRows:"6",  // Responsive design, for small windows sets NoVScroll and NoHScroll
   Sort:'P,M', // To sort grid according to partner and Month for first time (when no configuration saved)
   MaxGroupLength:'0', // Suppresses dividing rows to more groups when grouping because it is controlled by MaxChildren of all rows
   Group:'P', // To group grid by partner for first time (when no configuration saved)
   GroupRestoreSort:'1', // Restores sorting state after grouping that was before grouping
   GroupSortMain:'1', // When grouping always sorts according to main column ascending
   Adding:'0' , // Suppress adding new rows when grid is not grouped
   FilterEmpty:'1', // When filtering, hides group rows that have all children hidden, see the Group row have set CanFilter:'2'
   IndexEnum:'1', // All enums are set by index
   NameCol:'P', // Row will be identifies by Partner name in messages (e.g. in deleting rows)
   ExportFormat:'XLS', // Predefined export format is XLS, because XLSX is not supported by the DLL
   Size:'Low' // Smaller style size because of large grid
   },
Actions: { 
   OnUngroup:'Grid.Adding=0;',  // Suppress adding new rows when grid is not grouped
   OnRightClickCell:'Grid.Component.showCustomMenu(Row,Col)' // Custom event handler, shows the calling method of the framework component; Shows some custom popup menu on right click to any cell
   }, 
Lang: { 
   MenuExport: { ExportFormats:'XLS,CSV' },   // Listed only XLS and CSV, because XLSX is not supported by the DLL
   Alert: { ErrAdd:'Cannot add new partner here!' } // Changes text of adding error message
   }, 
Root: { AcceptDef:'' }, // By default (when no grouping is active) rows cannot be added or moved

Def: {
   // Base row settings,  AcceptDef='' means that no rows can be added or moved to children of the row
   R: { AcceptDef:'', CanEdit:'0', OCanEdit:'1', ICanEdit:'1', ECanEdit:'1', CalcOrder:'G,D,F' },

   // Base group setting - for group row created when grouping
   // It also inherits attributes from group row defined in Defaults.xml 
   // CanFilter='2' means that this row will be hidden when it does not have any visible children when filtering, see Cfg FilterEmpty 
   // Action suppresses adding new rows when grid is grouped by another column than Partner
        
   Group: { 
      CanFilter:'2', Calculated:'1', CalcOrder:'O,I,E,G,D,F,B', OCanEdit:'0', ICanEdit:'0', ECanEdit:'0',
      OFormula:'sum()', IFormula:'sum()', EFormula:'sum()', GFormula:'sum()', DFormula:'sum()', FFormula:'sum()',
      OnGroup:'Grid.Adding=0;' },

   // Group row created when grouping by partner (set by GroupCol attribute) 
   // GroupCols tells that this default row is used only when grid is grouped by Region, Country, State, Partner in that order
   // GroupMain tells to show grouped tree in Month column
   // This default is specific to this example and is used to edit all cells specific to Partner.
   // EditCols controls editing and bubbling changes to children.
   // This is the only row in this example that can be deleted, moved or added.
   // The children of this default are automatically created when this row added as new row (12 months).
   // ParentAcceptDef sets AcceptDef attribute of the parent row to let adding and moving this row
   GroupPartner : { 
      Def:'Group', GroupCol:'P', GroupCols:'|P|R,P|C,P|S,P|R,C,P|R,S,P|C,S,P|R,C,S,P', GroupMain:'M', GroupMainCaption:'Partner / Month',
      EditCols:'Main,R,C,S,X,N,A,B', CanDelete:'1', CanDrag:'1',
      OnGroup:'Grid.Adding=1;',
      P:'New partner', M:'New partner', MType:'Text', ParentAcceptDef:'GroupPartner', AcceptDef:'R', 
      Items: [
         { M:'0' },
         { M:'1' },
         { M:'2' },
         { M:'3' },
         { M:'4' },
         { M:'5' },
         { M:'6' },
         { M:'7' },
         { M:'8' },
         { M:'9' },
         { M:'10' },
         { M:'11' }
         ]
      },

   // Group row created when grouping by Region or Country or State 
   // GroupCols tells that this default row is used only when grid is grouped by Region, Country, State, Partner in that order
   // EditCols and .CopyTo attributes controls editing and bubbling changes to children.
   GroupLocPartner: {
      Def:'Group', GroupCol:'R,C,S', GroupCols:'|P|R,P|C,P|S,P|R,C,P|R,S,P|C,S,P|R,C,S,P|', GroupMain:'M', GroupMainCaption:'Location / Partner / Month',
      EditCols:'Main', RCopyTo:'Children,R', CCopyTo:'Children,C', SCopyTo:'Children,S',
      XVisible:'0', NVisible:'0', AVisible:'0', BVisible:'0'
      },

   // Group row created when grouping by Region or Country or State
   // GroupCols tells that this default row is used only when grid is grouped by Region, Country, State (without Partner!) in that order 
   // GroupMain tells to show grouped tree in Partner column
   // This default inherits attributes from GroupLocPartner and just changes some.
   GroupLoc: {
      Def:'GroupLocPartner', GroupCol:'R,C,S', GroupCols:'|R|C|S|R,C|R,S|C,S|R,C,S', GroupMain:'P', GroupMainCaption:'Location / Partner',
      MVisible:'0'
      },

   // Group row created for all other conditions than fulfilled by previous group rows.
   // It does not provide editing capabilities
   // It is usual grouping row defined in applications
   GroupOther: {
      Def:'Group', GroupMain:'P',
      MVisible:'0', RVisible:'0', CVisible:'0', SVisible:'0',
      XVisible:'0', NVisible:'0', AVisible:'0', BVisible:'0'
      }
   },

LeftCols: [

   // Partner, main column for other groupings, width 130px, when grouped 200px 
   // Shows value as tooltip
   // Is in one group with Month because of spanned fixed row - cannot be moved outside the group
   { Name:'P', Width:'130', GroupWidth:'210', Type:'Text', ToolTip:'1', Group:'1' },

   // Month, main column for grouping by partner, width 80px, when grouped 200px
   { Name:'M', Width:'80', GroupWidth:'210', Type:'Enum', Group:'1', Enum:'|01/2004|02/2004|03/2004|04/2004|05/2004|06/2004|07/2004|08/2004|09/2004|10/2004|11/2004|12/2004' }
         
   ],

Cols: [

   // Region
   { Name:'R', Width:'180', Type:'Enum', Refresh:'C,S', Group:'1',
     Enum:'|Central & South Asia|East Asia & the Pacific|East Europe|Middle East & North Africa|North & Central America|South America|Sub-Saharan Africa|West Europe'
     },

   // Country 
   //  The 'C' column is related to 'R' column, it contains only countries from selected region
   { Name:'C', Width:'130', Type:'Enum', Related:'R', Refresh:'S', Group:'1', IntFormat:'(unknown)',
     Enum0:"|ARMENIA|AZERBAIJAN|BANGLADESH|INDIA|KAZAKSTAN|PAKISTAN|SRI LANKA",
     Enum1:"|AUSTRALIA|BRUNEI|CHINA|HONG KONG|INDONESIA|JAPAN|KOREA, DPR|MALAYSIA|MONGOLIA|MYANMAR|NEW ZEALAND|PAPUA NEW GUINEA|PHILIPPINES|SINGAPORE|SOUTH KOREA|TAIWAN|THAILAND|VIETNAM",
     Enum2:"|ALBANIA|BELARUS|BULGARIA|CROATIA|CZECH REPUBLIC|ESTONIA|HUNGARY|LATVIA|LITHUANIA|MOLDOVA|POLAND|ROMANIA|RUSSIA|SERBIA-MONTENEGRO|SLOVAK REPUBLIC|SLOVENIA|UKRAINE",
     Enum3:"|ALGERIA|BAHRAIN|EGYPT|IRAN|IRAQ|ISRAEL|JORDAN|KUWAIT|LEBANON|LIBYA|MOROCCO|OMAN|QATAR|SAUDI ARABIA|SYRIA|TUNISIA|UAE|YEMEN",
     Enum4:"|BAHAMAS|CANADA|COSTA RICA|CUBA|DOMINICAN REPUBLIC|EL SALVADOR|GUATEMALA|HAITI|HONDURAS|JAMAICA|MEXICO|NICARAGUA|PANAMA|TRINIDAD & TOBAGO|UNITED STATES",
     Enum5:"|ARGENTINA|BOLIVIA|BRAZIL|CHILE|COLOMBIA|ECUADOR|GUYANA|PARAGUAY|PERU|SURINAME|URUGUAY|VENEZUELA",
     Enum6:"|ANGOLA|BOTSWANA|BURKINA FASO|CAMEROON|CONGO|CONGO DR|COTE D'IVOIRE|ETHIOPIA|GABON|GAMBIA|GHANA|GUINEA|GUINEA-BISSAU|KENYA|LIBERIA|MADAGASCAR|MALAWI|MALI|MOZAMBIQUE|NAMIBIA|NIGER|NIGERIA|SENEGAL|SIERRA LEONE|SOMALIA|SOUTH AFRICA|SUDAN|TANZANIA|TOGO|UGANDA|ZAMBIA|ZIMBABWE",
     Enum7:"|AUSTRIA|BELGIUM|CYPRUS|DENMARK|FINLAND|FRANCE|GERMANY|GREECE|ICELAND|IRELAND|ITALY|LUXEMBOURG|MALTA|NETHERLANDS|NORWAY|PORTUGAL|SPAIN|SWEDEN|SWITZERLAND|TURKEY|UNITED KINGDOM",
     },

   // State 
   // The 'S' column is related to 'C' column, it contains only states from selected country 
   // If the country is not divided to states, it is empty and read-only 
   // This column has set GroupType:16 - when grouping by State, it does not create groups for empty states
   { Name:'S', Width:'70', Type:'Enum', Related:'R,C', Group:'1', GroupEmpty:'0', IntFormat:'(unknown)',
     Enum4_14:"|Alabama|Alaska|Arizona|Arkansas|California|Colorado|Connecticut|Delaware|Florida|Georgia|Hawaii|Idaho|Illinois|Indiana|Iowa|Kansas|Kentucky|Louisiana|Maine|Maryland|Massachusetts|Michigan|Minnesota|Mississippi|Missouri|Montana|Nebraska|Nevada|New Hampshire|New Jersey|New Mexico|New York|North Carolina|North Dakota|Ohio|Oklahoma|Oregon|Pennsylvania|Rhode Island|South Carolina|South Dakota|Tennessee|Texas|Utah|Vermont|Virginia|Washington|West Virginia|Wisconsin|Wyoming"
     },

   { Name:'X', Width:'95', Type:'Bool', Format:'||x' }, // Registered
   { Name:'N', Width:'105', Type:'Date', Format:'d' }, // Since
   { Name:'A', Width:'80', Type:'Enum', Enum:'|week|month|quarter|half year|year' }, // Calls per
   { Name:'B', Width:'70', Type:'Int' }, // Rabat

   { Name:'O', Width:'70', Type:'Int' }, // Orders
   { Name:'I', Width:'90', Type:'Float', Format:',0.00' }, // Income
   { Name:'E', Width:'90', Type:'Float', Format:',0.00' }, // Expenses
   { Name:'G', Width:'100', Type:'Float', Format:',0.00', Formula:'I-E' }, // Gross profit
   { Name:'D', Width:'80', Type:'Float', Format:',0.00', Formula:'G>0?B*G/100:0' }, // Discount
   ],

 RightCols: [
   { Name:'F', Width:'90', Type:'Float', Format:',0.00', Formula:'G-D' } // Profit
   ],

// Column captions
Header: {
   R:'Region', C:'Country', S:'State', P:'Partner', M:'Month',
   X:'Registered', N:'Since', A:'Calls per', O:'Orders',
   I:'Income', E:'Expenses', G:'Gross profit', B:'Rabat', D:'Discount',
   F:'Profit' 
   },
Head : [
   // Filter row - to let user choose filter, changes filtering of area enums by selection only
   { Kind:'Filter', CanEdit:'1', PCaseSensitive:'0',
      RFilterOff:'(all)', RCanEmpty:'1', RShowMenu:'0',
      CFilterOff:'(all)', CCanEmpty:'1', CShowMenu:'0', 
      SFilterOff:'(all)', SCanEmpty:'1', SShowMenu:'0'
      }
   ],
Foot: [
   // Bottom row with the summary results
   { id:'$Results', CanDelete:'0', CanEdit:'0', Calculated:'1', 			
      Spanned:'1', P:'Total results', PSpan:'2', 
      RVisible:'0', CVisible:'0', SVisible:'0',
      XVisible:'0', AVisible:'0', 
      CalcOrder:'O,I,E,G,D,F,B',
      BType:'Float', BFormat:'0.00"%"', BFormula:'G?D/G*100:0',
      OFormula:'sum()', IFormula:'sum()', EFormula:'sum()', GFormula:'sum()', DFormula:'sum()', FFormula:'sum()',
      OCanEdit:'0', ICanEdit:'0', ECanEdit:'0'
      } 
   ],
Solid: [

   // Group row - to let user choose or build grouping
   { Kind:'Group', Cells:'Caption,List,Custom', Space:'0', MenuName:'Views',
      Caption:'Choose&nbsp;view:', CaptionWidth:'80', CaptionType:'Html', CaptionCanEdit:'0',
      ListHtmlPrefix:'<b>', ListHtmlPostfix:'</b>', ListWidth:'120',
      List:'|None|Partner|Location,&nbsp;Partner|Location|Month',
      Cols:'||P|R,C,S,P|R,C,S|M',
      ListCustom:'Other'
      }, 

   // Bottom simple pagers
   { Space:'4', Cells:'Pager,Pages', MenuName:'Bottom pager', CanPrint:'0',
      PagerType:'Pager', PagesLeft:'10', PagesType:'Pages', PagesRelWidth:'1', PagesCount:'10', PagesStep:'5'
      }
   ],
Pager: { Width:'160', MenuName:'Right pager' }, // Right side pager
}