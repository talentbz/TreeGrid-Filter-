{
Cfg: { CfgId:'Tree', MaxHeight:'1', MinTagHeight:'400', MainCol:'A', ShowDeleted:"0", SuppressCfg:'0', AutoSort:"0", DateStrings:'1', ReloadChanged:'3' },
Actions: { 
   OnRightClickCell:'Grid.Component.showCustomMenu(Row,Col)' // Custom event handler, shows the calling method of the framework component; Shows some custom popup menu on right click to any cell
   }, 
Def: {
   Data: { CalcOrder:'F,B', A:'Item', G:'0', H:'0', I:'', CDef:'' },
   Fixed: { CalcOrder:'D,B', EVisible:'0', FType:'Text', HVisible:'0', IType:'Text' },
   Node: { 
      CDef:'Data', Expanded:'1', Calculated:'1', CalcOrder:'D,F,B', 
      A:'Order', BFormula:'F-F*G/100+H', CCanEdit:'1', DFormula:'count()', DFormat:'0 "items"', 
      EFormat:' ', ECanEdit:'0', FFormula:'sum("B")', G:'0', H:'0', ICanEdit:'1' 
      }
   },
LeftCols: [
   { Name:'A', Width:'250', Type:'Text', ToolTip:'1' }
   ],
Cols: [
   { Name:'C', Width:'100', Type:'Text', CanEdit:'0' },
   { Name:'I', Width:'100', Type:'Date', Format:'d', CanEdit:'0' },
   { Name:'D', Width:'80', Type:'Int' },
   { Name:'E', Width:'90', Type:'Float', Format:'0.00' },
   { Name:'F', Width:'90', Type:'Float', Format:'0.00', Formula:'D*E' },
   { Name:'G', Width:'90', Type:'Int', Format:'0\%' },
   { Name:'H', Width:'90', Type:'Float', Format:'0.00' }
   ],
RightCols: [
   { Name:'B', Width:'90', Type:'Float', Format:'0.00', Formula:'F-F*G/100+H' }
   ],
Header: { A:"Product / Order name", B:"Price", C:"Customer", D:"Amount", E:"Unit price", F:"List Price", G:"Discount", H:"Shipping", I:"Date" },
Root: { CDef : 'Node' },
Foot: [
   { id:'Fix1', CanDelete:'0', CanEdit:'0', Calculated:'1', A:"Total income", BFormula:'sum()', DFormula:'count()', DFormat:'0 "orders"', DType:'Int', GVisible:'0' },
   { id:'Fix2', CanDelete:'0', CanEdit:'0', Calculated:'1', A:"Taxes", BFormula:'Get(Fix1,"B")*G/100', CType:'Text', DVisible:'0', G:"22", GCanEdit:'1' },
   { id:'Fix3', CanDelete:'0', CanEdit:'0', Calculated:'1', A:"Profit", BFormula:'Get(Fix1,"B")-Get(Fix2,"B")', CType:'Text', DVisible:'0', GVisible:'0' }
   ]
}