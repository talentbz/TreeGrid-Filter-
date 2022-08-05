<?php
header("Content-Type: text/xml; charset=utf-8");
header("Cache-Control: max-age=1; must-revalidate");
?>
<Grid>

   <Cfg id='Production document' SuppressAnimations='1'  Size="Low"  MainCol='document_type' ShowDeleted='1'/>
   <Cfg Validate='All,Added,Focus,Edit,Changed,Text' ValidateMessageTime='2500' ValidateMessage="Ju lutemi plotësoni të dhënat e nevojshme tek qelizat me ngjyrë të kuqe!"/>
   <Cfg SearchCells='1' SearchAction='Select' SearchHidden='1'/>
   <Cfg RowIndex='Nr.' RowIndexWidth='80' />
   <Cfg Paging="3" PageLength="100" MaxPages="1" PageTime='1' RemoveUnusedPages ='2'/>
   <Cfg PageLengthDiv="10" FastPages="500"/>
   <Cfg ChildPaging="3" RemoveCollapsed="2"/>
   <Cfg ChildParts="2" ChildPartLength="100" ChildPartMin="0" MaxChildParts="4"/>
   <Cfg CopyPasteTree ='3'/>
   <Cfg Alternate='2'/>   
   <Cfg ConstHeight='1' ConstWidth='1'/>
   <Cfg AutoCalendar='1'/> 
   <Cfg ExactSize='0'/>
   <Cfg SelectingCells='1' />
   <Cfg ConstHeight='1'/>
   <Cfg ExportType='Expanded,Outline'/>
   <Cfg CalculateSelected='1'/> 
   <Cfg PrintVarHeight='2'/>
   <Cfg Undo='1'/>
   <Cfg SuppressMessage='2'/>
   <Cfg NumberId='1' IdChars='0123456789'/> 
   <Cfg PrintPagePrefix="&lt;center class='%9' style='width:%7px'>First example printed page %3 from %6&lt;/center>"/>
   <Cfg PrintPagePostfix="&lt;center class='%9' style='width:%7px'>Page %1 horizontally from %4 , page %2 vertically from %5&lt;/center>"/>
   <Cfg Language='EN'/>
   <Colors Alternate="rgb(247,247,247)"/>
   <Actions OnDel="ClearValue"/>
   <Actions OnMouseOverEditable="Grid.MouseCursor('url(aero_prec.cur),default')"/>
   <Pager Width="150" Visible='0'/>

   <Def>   

      <D Name='Data' CDef='' AcceptDef='Data' Calculated='1' Spanned='1' Expanded='1' FormulaSuggest="6"
         
         document_type='Trup dokumenti' document_typeType='Text' document_typeSpan="3" document_typeCanEdit="0" document_typeCanFocus='0'
         posting_date='' posting_dateType='Text' posting_dateSpan="8" posting_dateCanEdit="0" posting_dateCanFocus='0'
         warehouseman="Aprovimet" warehousemanSpan="9" warehousemanAlign="Center" responsibilityCanEdit='0' responsibilityCanFocus='0'

        credit_quantityCanEdit='1'
        debit_quantityCanEdit='1'
         
         note="Konfigurime" noteSpan="6" noteAlign="Center" noteCanEdit='0' noteCanFocus='0'
         status='0'
         approved='0'

      />

      <D Name='Node' Parent='#Body' CDef='Data' Sorted='1' AcceptDef='Data' Spanned='1'  Expanded='1' CanFilter='1' 
         
         name='Trup dokumenti' nameSpan="8" nameCanEdit="2" nameCanFocus='0' nameAlign="Center" 
         credit_quantityFormula='sum()' credit_quantityCanEdit='2'
         debit_quantityFormula='sum()'  debit_quantityCanEdit='2'
         approvedBoolIcon="|Red.svg|Green.svg"
         warehouseman_approveBoolIcon="|Red.svg|Green.svg"
         manufacturer_approveBoolIcon="|Red.svg|Green.svg"
         debitCanEdit='1'   debitFormula='sum()'
         creditCanEdit='1'   creditFormula='sum()' 

         costCanEdit='2'   costFormula='sum()' 
         priceCanEdit='2'  priceFormula='sum()' 
         valueCanEdit='2'  valueFormula='sum()'
         raw_materialCanEdit='2' raw_materialForimula='sum()'
         productCanEdit='2' productForimula='sum()'
         
         warehouseman_approve='1'
         manufacturer_approve='1'
         status='1'
         approved='1'

      />
   </Def>

      <LeftCols>
      <C Name='document_type'  Width='150' Type='Text' CanEdit='1' VarHeight='1'   CaseSensitive='0' />
      <C Name='document_abbrevation' Width='150' Type='Text' CanEdit='1'  CaseSensitive='0' VarHeight='1' />
      <C Name='document_no' Width='150' Type='Text' CanEdit='1'  CaseSensitive='0' VarHeight='1' />
      </LeftCols>

   <Cols>
      <C Name='posting_date' Width='150'  Type='Date' Format="yyyy-MM-dd" CaseSensitive='0' CanEdit='2'   VarHeight='1'/>
      <C Name='document_date' Width='150'  Type='Date' Format="dd/mm/yyyy" CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='warehouse_origin' Width='150' Type='Text' CanEdit='1'  CaseSensitive='0' VarHeight='1' />
      <C Name='warehouse_origin_code' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='warehouse_destination' Width='150' Type='Text' CanEdit='1'  CaseSensitive='0' VarHeight='1' />
      <C Name='warehouse_destination_code' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='company' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='company_vat_no' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='name' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='code'  Width='150' Type='Text' CanEdit='2' VarHeight='1'   CaseSensitive='0' />
      <C Name='type'  Width='150' Type='Text' CanEdit='2' VarHeight='1'   CaseSensitive='0' />
      <C Name='barcode' Width='150' Type='Text' CaseSensitive='0' CanEdit='2'   VarHeight='1'/>
      <C Name='brand'  Width='150' Type='Text' CanEdit='2' VarHeight='1'   CaseSensitive='0' />
      <C Name='subcategory' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='category' Width='150' Type='Text' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='unit' Width='150' Type='Text' VarHeight='2' CanSort='1' CanEdit='1'/>
      <C Name='cost' Width='150' Type='Float' Format='0.00' CaseSensitive='0' CanEdit='2'   VarHeight='1' />
      <C Name='price' Width='150' Type='Float' Format='0.00' CaseSensitive='0' CanEdit='2'   VarHeight='1'/>
      <C Name='debit_quantity' Width='150' Type='Float' Format='0.00' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='credit_quantity' Width='150' Type='Float' Format='0.00' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='warehouseman' Width='150' Type='Text' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='warehouseman_department' Width='150' Type='Text' CaseSensitive='0' CanEdit='2'   VarHeight='1'/>
      <C Name='warehouseman_approve' Width='150' Type='Bool' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='deliveryman' Width='150' Type='Text' CaseSensitive='0' CanEdit='2'   VarHeight='1'/>
      <C Name='deliveryman_department' Width='150' Type='Text' CaseSensitive='0' CanEdit='2'   VarHeight='1'/>
      <C Name='deliveryman_approve' Width='150' Type='Bool' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='warehouseman_destination' Width='150' Type='Text' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='warehouseman_destination_department' Width='150' Type='Text' CaseSensitive='0' CanEdit='2'   VarHeight='1'/>
      <C Name='warehouseman_destination_approve' Width='150' Type='Bool' CaseSensitive='0' CanEdit='1'   VarHeight='1'/>
      <C Name='note' Width='150' Type='Text' VarHeight='1' CanSort='1' CanEdit='1'/>
      <C Name='status' Width='150' Type='Bool' VarHeight='1' CanSort='1' CanEdit='2'/>
      <C Name='item_uuid' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='warehouse_origin_uuid' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='warehouse_destination_uuid' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
      <C Name='uuid' Width='150' Type='Text' CanEdit='2'  CaseSensitive='0' VarHeight='1' />
   </Cols>    



    <Solid>     

      <Group Space='1' Calculated='1' Panel='1' id='Group' CanFocus='0' NoUpload="0" CanGroup='1'/>
      
      <Search Space='1' Panel='1' id='Search' CanFocus='0' NoUpload="0" Calculated='1'
         Cells='Case,Type,Expression,Sep1,Filter,Select,Mark,Find,Clear,Sep2'
         ExpressionAction='Last' ExpressionNoColor='0' ExpressionCanFocus='1' ExpressionLeft='5' ExpressionMinWidth='50'
         ExpressionEmptyValue ='&lt;s>Kërkoni...&lt;/s>' 
         CaseLeft="5" CaseLabelRight="Karaktere të ndjeshme"
         TypeLeft="5" TypeLabelRight="Qeliza" 
         Sep1Width="5" Sep1Type="Html" 
         Sep2Width="5" Sep2Type="Html"
         CanPrint='5' DefsPrintHPage='1' CasePrintHPage='1' TypePrintHPage='1' 
         ExpressionPrintHPage='2' Sep1PrintHPage='2' FilterPrintHPage='2' SelectPrintHPage='2' MarkPrintHPage='2' FindPrintHPage='2' ClearCanPrint='0' HelpCanPrint='0' Sep2PrintHPage='2'
         />
   </Solid>

   <Head>
      <Header id="Header3" CDef = 'Node' Spanned='1' Calculated='1'  SortIcons='0' CanFilter='3' 

      Nr.=''
      warehouse_origin='Njësi shërbimi / Origjina' warehouse_originSpan='2' warehouse_originAlign="Scroll" A0="A0 centered &amp; scrolled"
      warehouse_destination='Njësi shërbimi / Destinacioni'  warehouse_destinationSpan='2' warehouse_destinationAlign="Scroll" A0="A0 centered &amp; scrolled"
      company='Kompania' companySpan='2' companyAlign="Scroll" A0="A0 centered &amp; scrolled"
      debit_quantity='Njësi shërbimi Debit / Kredit' debit_quantitySpan='2' debit_quantityAlign="Scroll" A0="A0 centered &amp; scrolled"
      document_type='Dokument' document_typeSpan='3' document_typeAlign="Scroll" A0="A0 centered &amp; scrolled"
      posting_date="Data" posting_dateSpan="2" posting_dateAlign="Scroll" A0="A0 centered &amp; scrolled"
      warehouse="Njësi shërbimi" warehouseSpan="4" warehouseAlign="Scroll" A0="A0 centered &amp; scrolled"
      recipes_document="Receptura" recipes_documentSpan="4" recipes_documentAlign="Scroll" A0="A0 centered &amp; scrolled"
      deliveryman='Transporti' deliverymanSpan="3" deliverymanAlign="Scroll" A0="A0 centered &amp; scrolled"
      warehouseman="Magazinieri / Origjina" warehousemanSpan="3" warehousemanAlign="Scroll" A0="A0 centered &amp; scrolled"
      manufacturer="Prodhuesi" manufacturerSpan="3" manufacturerAlign="Scroll" A0="A0 centered &amp; scrolled"
      warehouseman_destination='Njësi shërbimi / Destinacioni' warehouseman_destinationSpan="3" warehouseman_destinationAlign="Scroll" A0="A0 centered &amp; scrolled"
      type='Artikull' typeSpan="2" typeAlign="Scroll" A0="A0 centered &amp; scrolled"
      name="Trup dokumenti / Artikuj" nameSpan="10" nameAlign="Scroll" A0="A0 centered &amp; scrolled"
      unit="Njësitë" unitSpan="3" unitAlign="Scroll"
      raw_material_unit="Njësia" raw_material_unitSpan="2" raw_material_unitAlign="Scroll"
      raw_material="Sasia" raw_materialSpan="2" raw_materialAlign="Scroll"
      debit="Debiti / Krediti" debitSpan="2" debitAlign="Scroll"
      cost="Total" costSpan="3" costAlign="Scroll"
      inventory_raw_material_account="Llogari kontabël" inventory_raw_material_accountSpan="2" inventory_raw_material_accountAlign="Scroll"
      responsibility="Përgjegjësi recepturës" responsibilitySpan="3" responsibilityAlign="Scroll"
      note="Konfigurime" noteSpan="6" noteAlign="Scroll" A0="A0 centered &amp; scrolled"
      />
   </Head> 

   <Head>

   <Header id="Header2"  CDef = 'Node' Calculated='1'  Spanned='1'  CanFilter='3' CanDelete='1' CanSelect='1'
      debit_quantity='Sasia në debi'
      credit_quantity='Sasia në kredi'
      warehouse_origin='Njësi shërbimi'
      deliveryman='Përshkrimi'
      deliveryman_department='Departamenti'
      deliveryman_approve='Aprovimi'
      warehouseman_destination='Përshkrimi'
      warehouseman_destination_department='Departamenti'
      warehouseman_destination_approve='Aprovimi'
      item_uuid='Identifikuesi artikullit'
      warehouse_origin_uuid='Identifikuesi origjinës'
      warehouse_destination_uuid='Identifikuesi destinacionit'
      warehouse_origin_code='Kodi'
      warehouse_destination='Njësi shërbimi'
      warehouse_destination_code='Kodi'
      posting_date='Datë postimi'
      document_date='Datë dokumenti'
      document_no='Nr. dokumenti'
      recipes_document='Dokumenti'
      recipes_document_abbrevation='Lloji'
      recipes_document_no='Nr. dokumenti'
      recipes_document_date='Datë dokumenti'
      warehouse='Përshkrimi'
      warehouse_code='Kodi'
      company='Kompania'
      company_vat_no='NIPT'
      document_type='Dokumenti'
      document_abbrevation='Lloji'
      create_date='Datë dokumenti'
      raw_material='Sasia e lëndës parë'
      product='Sasia e produktit'
      name='Përshkrimi'
      debit='Debiti'
      credit='Krediti'
      value='Vlera'
      brand='Marka'
      inventory_product_account='Produkti'
      inventory_raw_material_account='Lëndë e parë'
      responsibility='Përshkrimi'
      warehouseman='Përshkrimi'
      warehouseman_department='Departamenti'
      warehouseman_approve='Aprovimi'
      manufacturer='Përshkrimi'
      manufacturer_department='Departamenti'
      manufacturer_approve='Aprovimi'
      warehouse_uuid='Identifikuesi unik' warehouse_uuidSpan='2' warehouse_uuidAlign="Scroll"
      department='Departamenti'
      approved='Aprovimi'
      type='Tipi'
      barcode='Barkodi'
      category='Kategoria'
      subcategory='Nën kategoria'
      unit='Njësia bazë'
      purchase_unit='Njësia blerjes'
      sales_unit='Njësia shitjes'
      cost='Kosto'
      price='Çmim'
      tax_code='Tatim'
      tax_value='Vlera tatimit në %'
      posting_groups='Grup postimi'
      inventory_account='Llogari inventari'
      purchase_account='Llogari blerje'
      sales_account='Llogari shitje'
      depreciation_account='Llogari amortizimi'
      asset_depreciation_account='Llogari shpenzime amortizimi'
      tax_account='Llogari tatimi'
      valid_from='Nga data : '
      valid_to='Deri më datë : '
      purchase_status='Status blerje'
      sales_status='Status shitje'
      code='Kodi'
      note='Shënim'
      uuid='Identifikuesi unik'
      status='Statusi'
      inventory_effect='Efekt inventari'
      />
   </Head>

   <Head>
   <Filter id='Filter'   Calculated='1' CaseSensitive='0' 
      document_typeOnChange='Grid.Source.Page.Param.document_type=Row[Col];Grid.Source.Data.Param.document_type=Row[Col];Grid.Source.Check.Param.document_type=Row[Col];'
      document_dateOnChange='Grid.Source.Page.Param. document_date=Row[Col];Grid.Source.Data.Param.document_date=Row[Col];Grid.ReloadBody();'
      subcategorySuggest='|*RowsCanFilter'
      nameSuggest='|*RowsCanFilter'
      codeSuggest='|*RowsCanFilter'
      barcodeSuggest='|*RowsCanFilter'
      costSuggest='|*RowsCanFilter'
      priceSuggest='|*RowsCanFilter'
      noteSuggest='|*RowsCanFilter'
      costSuggest='|*RowsCanFilter'
      priceSuggest='|*RowsCanFilter'
      tax_valueSuggest='|*RowsCanFilter'
      inventory_accountSuggest='|*RowsCanFilter'
      purchase_accountSuggest='|*RowsCanFilter'
      sales_accountSuggest='|*RowsCanFilter'
      depreciation_accountSuggest='|*RowsCanFilter'
      asset_depreciation_accountSuggest='|*RowsCanFilter'
      tax_accountSuggest='|*RowsCanFilter'
      noteSuggest='|*RowsCanFilter'
      uuidSuggest='|*RowsCanFilter'
      purchase_unitSuggest='|*RowsCanFilter'
      sales_unitSuggest='|*RowsCanFilter'
      document_abbrevationOnChange='Grid.Source.Data.Param.document_abbrevation=Row[Col];Grid.Source.Page.Param.document_abbrevation=Row[Col];'
      />
   </Head>

   <Header id="Header1" CDef = 'Node' Spanned='1'  Calculated='1' CanDelete='0' CanSelect='0'  SortIcons='0' CanFilter='3' 
      posting_date="TRANSFERTA" posting_dateSpan="35" posting_dateAlign="Scroll" A0="A0 centered &amp; scrolled"
      responsibility="APROVIMET" responsibilitySpan="9" responsibilityAlign="Scroll" A0="A0 centered &amp; scrolled"
      responsibility="APROVIMET" responsibilitySpan="9" responsibilityAlign="Scroll" A0="A0 centered &amp; scrolled"
      note="Informacion" noteSpan="4" noteAlign="Scroll" A0="A0 centered &amp; scrolled"
      document_type='' document_typeSpan='3'
      Nr.=''
      type='' typeSpan='2'
      brand='Marka'
      barcode='Barkodi'
      category='Kategoria'
      subcategory='Nën kategoria'
      unit='Njësia bazë'
      purchase_unit='Njësia blerjes'
      sales_unit='Njësia shitjes'
      cost='Kosto'
      price='Çmim'
      tax_code='Tatim'
      tax_value='Vlera tatimit në %'
      posting_groups='Grup postimi'
      inventory_account='Llogari inventari'
      purchase_account='Llogari blerje'
      sales_account='Llogari shitje'
      depreciation_account='Llogari amortizimi'
      asset_depreciation_account='Llogari shpenzime amortizimi'
      tax_account='Llogari tatimi'
      valid_from='Nga data : '
      valid_to='Deri më datë : '
      purchase_status='Status blerje'
      sales_status='Status shitje'

      uuid='Identifikuesi unik'
      status='Statusi'
      inventory_effect='Efekt inventari'

   />
      
   <Foot>
         <I id='Fix1' Def='Foot' CanDelete='0' CanEdit='2' Calculated='1'  Spanned='1' CanSelect='0' CanFilter='0' 
         
         document_type='Total'
         document_abbrevationFormula='count(1)+" / "+count()+" "'
         create_dateFormula='min("create_date")+"~"+max("create_date")' create_dateRange='1'
         document_dateFormula='min("document_date")+"~"+max("document_date")' document_dateRange='1' 
         posting_dateFormula='min("posting_date")+"~"+max("posting_date")' posting_dateRange='1'
         recipes_document_dateFormula='min("recipes_document_date")+"~"+max("recipes_document_date")' recipes_document_dateRange='1'
         name='' nameSpan='8'
         recipes_documentFormula='count(recipes_document==1)+"  recepturë(a)"' recipes_documentSpan='2'
         

         inventory_raw_material_account='' inventory_raw_material_accountSpan='2'
         credit_quantityFormula='sum()' credit_quantityType='Float' credit_quantityFormat='0.00'
         debit_quantityFormula='sum()' debit_quantityType='Float' debit_quantityFormat='0.00'
         costFormula='sum()' 
         priceFormula='sum()' 
         valueFormula='sum()'
         raw_materialFormula='sum()'
         productFormula='sum()'
         debitFormula='sum()'
         creditFormula='sum()'

         noteFormula='count("status==1")+"  aktiv(ë)"' noteSpan='4'
         responsibilityFormula='count("approved==1")+"  aprovuar(a)"' responsibilitySpan='3'
         manufacturerFormula='count("manufacturer_approve==1")+"  aprovuar(a)"' manufacturerSpan='3'
         warehousemanFormula='count("warehouseman_approve==1")+"  aprovuar(a)"'  warehousemanSpan='3'

         raw_materialCanEdit='2' raw_materialFormula='sum("status==1")'
         productCanEdit='2' productFormula='sum()'
          />
          
   </Foot>
    <Toolbar Kind='Toolbar1'Formula='count("X==1")+" / "+count(1)+" document(s)"' Space='5' Styles='0'  Contrast='0' Size='0' Indent='0' Outdent='0' Language='0'/>

        <Toolbar Kind='Toolbar2' Formula='count("X==1")+" / "+count(1)+" document(s)"' Space='-1' Styles='0'  Contrast='0' Size='0' Indent='0' Outdent='0' Language='0'/>
</Grid>
