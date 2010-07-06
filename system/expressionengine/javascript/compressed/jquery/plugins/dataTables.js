/*
 * File:        jquery.dataTables.js
 * Version:     1.6.2
 * CVS:         $Id$
 * Description: Paginate, search and sort HTML tables
 * Author:      Allan Jardine (www.sprymedia.co.uk)
 * Created:     28/3/2008
 * Modified:    $Date$ by $Author$
 * Language:    Javascript
 * License:     GPL v2 or BSD 3 point style
 * Project:     Mtaala
 * Contact:     allan.jardine@sprymedia.co.uk
 * 
 * Copyright 2008-2010 Allan Jardine, all rights reserved.
 *
 * This source file is free software, under either the GPL v2 license or a
 * BSD style license, as supplied with this software.
 * 
 * This source file is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
 * or FITNESS FOR A PARTICULAR PURPOSE. See the license files for details.
 * 
 * For details pleease refer to: http://www.datatables.net
 */
/*
 * When considering jsLint, we need to allow eval() as it it is used for reading cookies and 
 * building the dynamic multi-column sort functions.
 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Section - DataTables variables
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*
	 * Variable: dataTableSettings
	 * Purpose:  Store the settings for each dataTables instance
	 * Scope:    jQuery.fn
	 */
/* Short reference for fast internal lookup */
/*
	 * Variable: dataTableExt
	 * Purpose:  Container for customisable parts of DataTables
	 * Scope:    jQuery.fn
	 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Section - DataTables extensible objects
	 * 
	 * The _oExt object is used to provide an area where user dfined plugins can be 
	 * added to DataTables. The following properties of the object are used:
	 *   oApi - Plug-in API functions
	 *   aTypes - Auto-detection of types
	 *   oSort - Sorting functions used by DataTables (based on the type)
	 *   oPagination - Pagination functions for different input styles
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*
	 * Variable: sVersion
	 * Purpose:  Version string for plug-ins to check compatibility
	 * Scope:    jQuery.fn.dataTableExt
	 * Notes:    Allowed format is a.b.c.d.e where:
	 *   a:int, b:int, c:int, d:string(dev|beta), e:int. d and e are optional
	 */
/*
	 * Variable: iApiIndex
	 * Purpose:  Index for what 'this' index API functions should use
	 * Scope:    jQuery.fn.dataTableExt
	 */
/*
	 * Variable: oApi
	 * Purpose:  Container for plugin API functions
	 * Scope:    jQuery.fn.dataTableExt
	 */
/*
	 * Variable: aFiltering
	 * Purpose:  Container for plugin filtering functions
	 * Scope:    jQuery.fn.dataTableExt
	 */
/*
	 * Variable: aoFeatures
	 * Purpose:  Container for plugin function functions
	 * Scope:    jQuery.fn.dataTableExt
	 * Notes:    Array of objects with the following parameters:
	 *   fnInit: Function for initialisation of Feature. Takes oSettings and returns node
	 *   cFeature: Character that will be matched in sDom - case sensitive
	 *   sFeature: Feature name - just for completeness :-)
	 */
/*
	 * Variable: ofnSearch
	 * Purpose:  Container for custom filtering functions
	 * Scope:    jQuery.fn.dataTableExt
	 * Notes:    This is an object (the name should match the type) for custom filtering function,
	 *   which can be used for live DOM checking or formatted text filtering
	 */
/*
	 * Variable: afnSortData
	 * Purpose:  Container for custom sorting data source functions
	 * Scope:    jQuery.fn.dataTableExt
	 * Notes:    Array (associative) of functions which is run prior to a column of this 
	 *   'SortDataType' being sorted upon.
	 *   Function input parameters:
	 *     object:oSettings-  DataTables settings object
	 *     int:iColumn - Target column number
	 *   Return value: Array of data which exactly matched the full data set size for the column to
	 *     be sorted upon
	 */
/*
	 * Variable: oStdClasses
	 * Purpose:  Storage for the various classes that DataTables uses
	 * Scope:    jQuery.fn.dataTableExt
	 */
/* Two buttons buttons */
/* Full numbers paging buttons */
/* Stripping classes */
/* Empty row */
/* Features */
/* Note that the type is postfixed */
/* Sorting */
/* Sortable in both directions */
/* Note that an int is postfixed for the sorting order */
/*
	 * Variable: oJUIClasses
	 * Purpose:  Storage for the various classes that DataTables uses - jQuery UI suitable
	 * Scope:    jQuery.fn.dataTableExt
	 */
/* Two buttons buttons */
/* Full numbers paging buttons */
/* Stripping classes */
/* Empty row */
/* Features */
/* Note that the type is postfixed */
/* Sorting */
/* Note that an int is postfixed for the sorting order */
/*
	 * Variable: oPagination
	 * Purpose:  Container for the various type of pagination that dataTables supports
	 * Scope:    jQuery.fn.dataTableExt
	 */
/*
		 * Variable: two_button
		 * Purpose:  Standard two button (forward/back) pagination
	 	 * Scope:    jQuery.fn.dataTableExt.oPagination
		 */
/*
			 * Function: oPagination.two_button.fnInit
			 * Purpose:  Initalise dom elements required for pagination with forward/back buttons only
			 * Returns:  -
	 		 * Inputs:   object:oSettings - dataTables settings object
	     *           node:nPaging - the DIV which contains this pagination control
			 *           function:fnCallbackDraw - draw function which must be called on update
			 */
/* Store the next and previous elements in the oSettings object as they can be very
				 * usful for automation - particularly testing
				 */
/* Only draw when the page has actually changed */
/* Take the brutal approach to cancelling text selection */
/* ID the first elements only */
/*
			 * Function: oPagination.two_button.fnUpdate
			 * Purpose:  Update the two button pagination at the end of the draw
			 * Returns:  -
	 		 * Inputs:   object:oSettings - dataTables settings object
			 *           function:fnCallbackDraw - draw function to call on page change
			 */
/* Loop over each instance of the pager */
/*
		 * Variable: iFullNumbersShowPages
		 * Purpose:  Change the number of pages which can be seen
	 	 * Scope:    jQuery.fn.dataTableExt.oPagination
		 */
/*
		 * Variable: full_numbers
		 * Purpose:  Full numbers pagination
	 	 * Scope:    jQuery.fn.dataTableExt.oPagination
		 */
/*
			 * Function: oPagination.full_numbers.fnInit
			 * Purpose:  Initalise dom elements required for pagination with a list of the pages
			 * Returns:  -
	 		 * Inputs:   object:oSettings - dataTables settings object
	     *           node:nPaging - the DIV which contains this pagination control
			 *           function:fnCallbackDraw - draw function which must be called on update
			 */
/* Take the brutal approach to cancelling text selection */
/* ID the first elements only */
/*
			 * Function: oPagination.full_numbers.fnUpdate
			 * Purpose:  Update the list of page buttons shows
			 * Returns:  -
	 		 * Inputs:   object:oSettings - dataTables settings object
			 *           function:fnCallbackDraw - draw function to call on page change
			 */
/* Pages calculation */
/* Build the dynamic list */
/* Loop over each instance of the pager */
/* Use the information in the element to jump to the required page */
/* Build up the dynamic list forst - html and listeners */
/* Update the 'premanent botton's classes */
/* EllisLab edit */
/* End EllisLab edit */
/*
	 * Variable: oSort
	 * Purpose:  Wrapper for the sorting functions that can be used in DataTables
	 * Scope:    jQuery.fn.dataTableExt
	 * Notes:    The functions provided in this object are basically standard javascript sort
	 *   functions - they expect two inputs which they then compare and then return a priority
	 *   result. For each sort method added, two functions need to be defined, an ascending sort and
	 *   a descending sort.
	 */
/*
		 * text sorting
		 */
/*
		 * html sorting (ignore html tags)
		 */
/*
		 * date sorting
		 */
/*
		 * numerical sorting
		 */
/*
	 * Variable: aTypes
	 * Purpose:  Container for the various type of type detection that dataTables supports
	 * Scope:    jQuery.fn.dataTableExt
	 * Notes:    The functions in this array are expected to parse a string to see if it is a data
	 *   type that it recognises. If so then the function should return the name of the type (a
	 *   corresponding sort function should be defined!), if the type is not recognised then the
	 *   function should return null such that the parser and move on to check the next type.
	 *   Note that ordering is important in this array - the functions are processed linearly,
	 *   starting at index 0.
	 */
/*
		 * Function: -
		 * Purpose:  Check to see if a string is numeric
		 * Returns:  string:'numeric' or null
		 * Inputs:   string:sText - string to check
		 */
/* Sanity check that we are dealing with a string or quick return for a number */
/* Check for a valid first char (no period and allow negatives) */
/* Check all the other characters are valid */
/* Only allowed one decimal place... */
/*
		 * Function: -
		 * Purpose:  Check to see if a string is actually a formatted date
		 * Returns:  string:'date' or null
		 * Inputs:   string:sText - string to check
		 */
/*
	 * Variable: _oExternConfig
	 * Purpose:  Store information for DataTables to access globally about other instances
	 * Scope:    jQuery.fn.dataTableExt
	 */
/* int:iNextUnique - next unique number for an instance */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Section - DataTables prototype
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*
	 * Function: dataTable
	 * Purpose:  DataTables information
	 * Returns:  -
	 * Inputs:   object:oInit - initalisation options for the table
	 */
/*
		 * Function: classSettings
		 * Purpose:  Settings container function for all 'class' properties which are required
		 *   by dataTables
		 * Returns:  -
		 * Inputs:   -
		 */
/*
			 * Variable: sInstance
			 * Purpose:  Unique idendifier for each instance of the DataTables object
			 * Scope:    jQuery.dataTable.classSettings 
			 */
/*
			 * Variable: oFeatures
			 * Purpose:  Indicate the enablement of key dataTable features
			 * Scope:    jQuery.dataTable.classSettings 
			 */
/*
			 * Variable: aanFeatures
			 * Purpose:  Array referencing the nodes which are used for the features
			 * Scope:    jQuery.dataTable.classSettings 
			 * Notes:    The parameters of this object match what is allowed by sDom - i.e.
			 *   'l' - Length changing
			 *   'f' - Filtering input
			 *   't' - The table!
			 *   'i' - Information
			 *   'p' - Pagination
			 *   'r' - pRocessing
			 */
/*
			 * Variable: oLanguage
			 * Purpose:  Store the language strings used by dataTables
			 * Scope:    jQuery.dataTable.classSettings
			 * Notes:    The words in the format _VAR_ are variables which are dynamically replaced
			 *   by javascript
			 */
/*
			 * Variable: aoData
			 * Purpose:  Store data information
			 * Scope:    jQuery.dataTable.classSettings 
			 * Notes:    This is an array of objects with the following parameters:
			 *   int: _iId - internal id for tracking
			 *   array: _aData - internal data - used for sorting / filtering etc
			 *   node: nTr - display node
			 *   array node: _anHidden - hidden TD nodes
			 *   string: _sRowStripe
			 */
/*
			 * Variable: aiDisplay
			 * Purpose:  Array of indexes which are in the current display (after filtering etc)
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: aiDisplayMaster
			 * Purpose:  Array of indexes for display - no filtering
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: aoColumns
			 * Purpose:  Store information about each column that is in use
			 * Scope:    jQuery.dataTable.classSettings 
			 */
/*
			 * Variable: iNextId
			 * Purpose:  Store the next unique id to be used for a new row
			 * Scope:    jQuery.dataTable.classSettings 
			 */
/*
			 * Variable: asDataSearch
			 * Purpose:  Search data array for regular expression searching
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: oPreviousSearch
			 * Purpose:  Store the previous search incase we want to force a re-search
			 *   or compare the old search to a new one
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: aoPreSearchCols
			 * Purpose:  Store the previous search for each column
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: aaSorting
			 * Purpose:  Sorting information
			 * Scope:    jQuery.dataTable.classSettings
			 * Notes:    Index 0 - column number
			 *           Index 1 - current sorting direction
			 *           Index 2 - index of asSorting for this column
			 */
/*
			 * Variable: aaSortingFixed
			 * Purpose:  Sorting information that is always applied
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: asStripClasses
			 * Purpose:  Classes to use for the striping of a table
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: fnRowCallback
			 * Purpose:  Call this function every time a row is inserted (draw)
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: fnHeaderCallback
			 * Purpose:  Callback function for the header on each draw
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: fnFooterCallback
			 * Purpose:  Callback function for the footer on each draw
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: aoDrawCallback
			 * Purpose:  Array of callback functions for draw callback functions
			 * Scope:    jQuery.dataTable.classSettings
			 * Notes:    Each array element is an object with the following parameters:
			 *   function:fn - function to call
			 *   string:sName - name callback (feature). useful for arranging array
			 */
/*
			 * Variable: fnInitComplete
			 * Purpose:  Callback function for when the table has been initalised
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: sTableId
			 * Purpose:  Cache the table ID for quick access
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: nTable
			 * Purpose:  Cache the table node for quick access
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: iDefaultSortIndex
			 * Purpose:  Sorting index which will be used by default
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: bInitialised
			 * Purpose:  Indicate if all required information has been read in
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: aoOpenRows
			 * Purpose:  Information about open rows
			 * Scope:    jQuery.dataTable.classSettings
			 * Notes:    Has the parameters 'nTr' and 'nParent'
			 */
/*
			 * Variable: sDom
			 * Purpose:  Dictate the positioning that the created elements will take
			 * Scope:    jQuery.dataTable.classSettings
			 * Notes:    
			 *   The following options are allowed:
			 *     'l' - Length changing
			 *     'f' - Filtering input
			 *     't' - The table!
			 *     'i' - Information
			 *     'p' - Pagination
			 *     'r' - pRocessing
			 *   The following constants are allowed:
			 *     'H' - jQueryUI theme "header" classes
			 *     'F' - jQueryUI theme "footer" classes
			 *   The following syntax is expected:
			 *     '<' and '>' - div elements
			 *     '<"class" and '>' - div with a class
			 *   Examples:
			 *     '<"wrapper"flipt>', '<lf<t>ip>'
			 */
/*
			 * Variable: sPaginationType
			 * Purpose:  Note which type of sorting should be used
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: iCookieDuration
			 * Purpose:  The cookie duration (for bStateSave) in seconds - default 2 hours
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: sAjaxSource
			 * Purpose:  Source url for AJAX data for the table
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: bAjaxDataGet
			 * Purpose:  Note if draw should be blocked while getting data
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: fnServerData
			 * Purpose:  Function to get the server-side data - can be overruled by the developer
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: iServerDraw
			 * Purpose:  Counter and tracker for server-side processing draws
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: _iDisplayLength, _iDisplayStart, _iDisplayEnd
			 * Purpose:  Display length variables
			 * Scope:    jQuery.dataTable.classSettings
			 * Notes:    These variable must NOT be used externally to get the data length. Rather, use
			 *   the fnRecordsTotal() (etc) functions.
			 */
/*
			 * Variable: _iRecordsTotal, _iRecordsDisplay
			 * Purpose:  Display length variables used for server side processing
			 * Scope:    jQuery.dataTable.classSettings
			 * Notes:    These variable must NOT be used externally to get the data length. Rather, use
			 *   the fnRecordsTotal() (etc) functions.
			 */
/*
			 * Variable: bJUI
			 * Purpose:  Should we add the markup needed for jQuery UI theming?
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: bJUI
			 * Purpose:  Should we add the markup needed for jQuery UI theming?
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
			 * Variable: bFiltered and bSorted
			 * Purpose:  Flag to allow callback functions to see what action has been performed
			 * Scope:    jQuery.dataTable.classSettings
			 */
/*
		 * Variable: oApi
		 * Purpose:  Container for publicly exposed 'private' functions
		 * Scope:    jQuery.dataTable
		 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - API functions
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*
		 * Function: fnDraw
		 * Purpose:  Redraw the table
		 * Returns:  -
		 * Inputs:   bool:bComplete - Refilter and resort (if enabled) the table before the draw.
		 *             Optional: default - true
		 */
/*
		 * Function: fnFilter
		 * Purpose:  Filter the input based on data
		 * Returns:  -
		 * Inputs:   string:sInput - string to filter the table on
		 *           int:iColumn - optional - column to limit filtering to
		 *           bool:bEscapeRegex - optional - escape regex characters or not - default true
		 */
/* Global filter */
/* Single column filter */
/*
		 * Function: fnSettings
		 * Purpose:  Get the settings for a particular table for extern. manipulation
		 * Returns:  -
		 * Inputs:   -
		 */
/*
		 * Function: fnVersionCheck
		 * Purpose:  Check a version string against this version of DataTables. Useful for plug-ins
		 * Returns:  bool:true -this version of DataTables is greater or equal to the required version
		 *                false -this version of DataTales is not suitable
		 * Inputs:   string:sVersion - the version to check against. May be in the following formats:
		 *             "a", "a.b" or "a.b.c"
		 * Notes:    This function will only check the first three parts of a version string. It is
		 *   assumed that beta and dev versions will meet the requirements. This might change in future
		 */
/* This is cheap, but very effective */
/*
		 * Function: fnSort
		 * Purpose:  Sort the table by a particular row
		 * Returns:  -
		 * Inputs:   int:iCol - the data index to sort on. Note that this will
		 *   not match the 'display index' if you have hidden data entries
		 */
/*
		 * Function: fnSortListener
		 * Purpose:  Attach a sort listener to an element for a given column
		 * Returns:  -
		 * Inputs:   node:nNode - the element to attach the sort listener to
		 *           int:iColumn - the column that a click on this node will sort on
		 *           function:fnCallback - callback function when sort is run - optional
		 */
/*
		 * Function: fnAddData
		 * Purpose:  Add new row(s) into the table
		 * Returns:  array int: array of indexes (aoData) which have been added (zero length on error)
		 * Inputs:   array:mData - the data to be added. The length must match
		 *               the original data from the DOM
		 *             or
		 *             array array:mData - 2D array of data to be added
		 *           bool:bRedraw - redraw the table or not - default true
		 * Notes:    Warning - the refilter here will cause the table to redraw
		 *             starting at zero
		 * Notes:    Thanks to Yekimov Denis for contributing the basis for this function!
		 */
/* Find settings from table node */
/* Check if we want to add multiple rows or not */
/* Rebuild the search */
/*
		 * Function: fnDeleteRow
		 * Purpose:  Remove a row for the table
		 * Returns:  array:aReturn - the row that was deleted
		 * Inputs:   mixed:mTarget - 
		 *             int: - index of aoData to be deleted, or
		 *             node(TR): - TR element you want to delete
		 *           function:fnCallBack - callback function - default null
		 *           bool:bNullRow - remove the row information from aoData by setting the value to
		 *             null - default false
		 * Notes:    This function requires a little explanation - we don't actually delete the data
		 *   from aoData - rather we remove it's references from aiDisplayMastr and aiDisplay. This
		 *   in effect prevnts DataTables from drawing it (hence deleting it) - it could be restored
		 *   if you really wanted. The reason for this is that actually removing the aoData object
		 *   would mess up all the subsequent indexes in the display arrays (they could be ajusted - 
		 *   but this appears to do what is required).
		 */
/* Find settings from table node */
/* Delete from the display master */
/* Delete from the current display index */
/* Rebuild the search */
/* If there is a user callback function - call it */
/* Check for an 'overflow' they case for dislaying the table */
/* Return the data array from this row */
/*
		 * Function: fnClearTable
		 * Purpose:  Quickly and simply clear a table
		 * Returns:  -
		 * Inputs:   bool:bRedraw - redraw the table or not - default true
		 * Notes:    Thanks to Yekimov Denis for contributing the basis for this function!
		 */
/* Find settings from table node */
/*
		 * Function: fnOpen
		 * Purpose:  Open a display row (append a row after the row in question)
		 * Returns:  node:nNewRow - the row opened
		 * Inputs:   node:nTr - the table row to 'open'
		 *           string:sHtml - the HTML to put into the row
		 *           string:sClass - class to give the new cell
		 */
/* Find settings from table node */
/* the old open one if there is one */
/* If the nTr isn't on the page at the moment - then we don't insert at the moment */
/* No point in storing the row if using server-side processing since the nParent will be
			 * nuked on a re-draw anyway
			 */
/*
		 * Function: fnClose
		 * Purpose:  Close a display row
		 * Returns:  int: 0 (success) or 1 (failed)
		 * Inputs:   node:nTr - the table row to 'close'
		 */
/* Find settings from table node */
/* Remove it if it is currently on display */
/*
		 * Function: fnGetData
		 * Purpose:  Return an array with the data which is used to make up the table
		 * Returns:  array array string: 2d data array ([row][column]) or array string: 1d data array
		 *           or
		 *           array string (if iRow specified)
		 * Inputs:   mixed:mRow - optional - if not present, then the full 2D array for the table 
		 *             if given then:
		 *               int: - return 1D array for aoData entry of this index
		 *               node(TR): - return 1D array for this TR element
		 * Inputs:   int:iRow - optional - if present then the array returned will be the data for
		 *             the row with the index 'iRow'
		 */
/*
		 * Function: fnGetNodes
		 * Purpose:  Return an array with the TR nodes used for drawing the table
		 * Returns:  array node: TR elements
		 *           or
		 *           node (if iRow specified)
		 * Inputs:   int:iRow - optional - if present then the array returned will be the node for
		 *             the row with the index 'iRow'
		 */
/*
		 * Function: fnGetPosition
		 * Purpose:  Get the array indexes of a particular cell from it's DOM element
		 * Returns:  int: - row index, or array[ int, int, int ]: - row index, column index (visible)
		 *             and column index including hidden columns
		 * Inputs:   node:nNode - this can either be a TR or a TD in the table, the return is
		 *             dependent on this input
		 */
/*
		 * Function: fnUpdate
		 * Purpose:  Update a table cell or row
		 * Returns:  int: 0 okay, 1 error
		 * Inputs:   array string 'or' string:mData - data to update the cell/row with
		 *           mixed:mRow - 
		 *             int: - index of aoData to be updated, or
		 *             node(TR): - TR element you want to update
		 *           int:iColumn - the column to update - optional (not used of mData is 2D)
		 *           bool:bRedraw - redraw the table or not - default true
		 */
/* Update the search array */
/* Redraw the table */
/*
		 * Function: fnShowColoumn
		 * Purpose:  Show a particular column
		 * Returns:  -
		 * Inputs:   int:iCol - the column whose display should be changed
		 *           bool:bShow - show (true) or hide (false) the column
		 */
/* No point in doing anything if we are requesting what is already true */
/* Show the column */
/* Need to decide if we should use appendChild or insertBefore */
/* Which coloumn should we be inserting before? */
/* Remove a column from display */
/* If there are any 'open' rows, then we need to alter the colspan for this col change */
/* Since there is no redraw done here, we need to save the state manually */
/*
		 * Function: fnPageChange
		 * Purpose:  Change the pagination
		 * Returns:  -
		 * Inputs:   string:sAction - paging action to take: "first", "previous", "next" or "last"
		 *           bool:bRedraw - redraw the table or not - optional - default true
		 */
/*
		 * Plugin API functions
		 * 
		 * This call will add the functions which are defined in _oExt.oApi to the
		 * DataTables object, providing a rather nice way to allow plug-in API functions. Note that
		 * this is done here, so that API function can actually override the built in API functions if
		 * required for a particular purpose.
		 */
/*
		 * Function: _fnExternApiFunc
		 * Purpose:  Create a wrapper function for exporting an internal func to an external API func
		 * Returns:  function: - wrapped function
		 * Inputs:   string:sFunc - API function name
		 */
/*
				 * Function: anon
				 * Purpose:  Wrap the plug-in API functions in order to provide the settings as 1st arg 
				 *   and execute in this scope
				 * Returns:  -
				 * Inputs:   -
				 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Local functions
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Initalisation
		 */
/*
		 * Function: _fnInitalise
		 * Purpose:  Draw the table for the first time, adding all required features
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Ensure that the table data is fully initialised */
/* Show the display HTML options */
/* Draw the headers for the table */
/* If there is default sorting required - let's do it. The sort function
			 * will do the drawing for us. Otherwise we draw the table
			 */
/*
				 * Add the sorting classes to the header and the body (if needed).
				 * Reason for doing it here after the first draw is to stop classes being applied to the
				 * 'static' table.
				 */
/* if there is an ajax source */
/* Got the data - add it to the table */
/* Reset the init display for cookie saving. We've already done a filter, and
					 * therefore cleared it before. So we need to make it appear 'fresh'
					 */
/* Run the init callback if there is one */
/* Run the init callback if there is one */
/*
		 * Function: _fnLanguageProcess
		 * Purpose:  Copy language variables from remote object to a local one
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           object:oLanguage - Language information
		 *           bool:bInit - init once complete
		 */
/*
		 * Function: _fnAddColumn
		 * Purpose:  Add a column to the list used for the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           object:oOptions - object with sType, bVisible and bSearchable
		 *           node:nTh - the th element for this column
		 * Notes:    All options in enter column can be over-ridden by the user
		 *   initialisation of dataTables
		 */
/* User specified column options */
/* Feature sorting overrides column specific when off */
/* Check that the class assignment is correct for sorting */
/* Add a column specific filter */
/* Don't require that the user must specify bEscapeRegex */
/*
		 * Function: _fnAddData
		 * Purpose:  Add a data array to the table, creating DOM node etc
		 * Returns:  int: - >=0 if successful (index of new aoData entry), -1 if failed
		 * Inputs:   object:oSettings - dataTables settings object
		 *           array:aData - data array to be added
		 */
/* Sanity check the length of the new array */
/* Create the object for storing information about this new row */
/* Create the cells */
/* Use the rendered data for filtering/sorting */
/* See if we should auto-detect the column type */
/* Attempt to auto detect the type - same as _fnGatherData() */
/* String is always the 'fallback' option */
/* Add to the display array */
/*
		 * Function: _fnGatherData
		 * Purpose:  Read in the data from the target table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
			 * Process by row first
			 * Add the data object for the whole table - storing the tr node. Note - no point in getting
			 * DOM based data if we are going to go and replace it with Ajax source data.
			 */
/* Gather in the TD elements of the Table - note that this is basically the same as
			 * fnGetTdNodes, but that function takes account of hidden columns, which we haven't yet
			 * setup!
			 */
/* Sanity check */
/* Now process by column */
/* Get the title of the column - unless there is a user set one */
/* A single loop to rule them all (and be more efficient) */
/* Type detection */
/* String is always the 'fallback' option */
/* Rendering */
/* Use the rendered data for filtering/sorting */
/* Classes */
/* Column visability */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Drawing functions
		 */
/*
		 * Function: _fnDrawHead
		 * Purpose:  Create the HTML header for the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* If there is a header in place - then use it - otherwise it's going to get nuked... */
/* We've got a thead from the DOM, so remove hidden columns and apply width to vis cols */
/* Set width */
/* Set the title of the column if it is user defined (not what was auto detected) */
/* We don't have a header in the DOM - so we are going to have to create one */
/* Add the extra markup needed by jQuery UI's themes */
/* Add sort listener */
/* Take the brutal approach to cancelling text selection due to the shift key */
/* Cache the footer elements */
/*
		 * Function: _fnDraw
		 * Purpose:  Insert the required TR nodes into the table for display
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* If we are dealing with Ajax - do it here */
/* Check and see if we have an initial draw position from state saving */
/* Remove the old stripping classes and then add the new one */
/* Custom row callback function - might want to manipule the row */
/* If there is an open row - and it is attached to this parent - attach it on redraw */
/* Table is empty - create a row with an empty message in it */
/* Callback the header and footer custom funcation if there is one */
/* 
			 * Need to remove any old row from the display - note we can't just empty the tbody using
			 * $().html('') since this will unbind the jQuery event handlers (even although the node 
			 * still exists!) - equally we can't use innerHTML, since IE throws an exception.
			 */
/* Put the draw table into the dom */
/* Call all required callback functions for the end of a draw */
/* Draw is complete, sorting and filtering must be as well */
/* Perform certain DOM operations after the table has been drawn for the first time */
/* Set an absolute width for the table such that pagination doesn't
				 * cause the table to resize
				 */
/*
		 * Function: _fnReDraw
		 * Purpose:  Redraw the table - taking account of the various features which are enabled
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Sorting will refilter and draw for us */
/* Filtering will redraw for us */
/*
		 * Function: _fnAjaxUpdate
		 * Purpose:  Update the table using an Ajax call
		 * Returns:  bool: block the table drawing or not
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Paging and general */
/* Filtering */
/* Sorting */
/*
		 * Function: _fnAjaxUpdateDraw
		 * Purpose:  Data the data from the server (nuking the old) and redraw the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           object:json - json data return from the server.
		 *             The following must be defined:
		 *               iTotalRecords, iTotalDisplayRecords, aaData
		 *             The following may be defined:
		 *               sColumns
		 */
/* Protect against old returns over-writing a new one. Possible when you get
				 * very fast interaction, and later queires are completed much faster
				 */
/* Determine if reordering is required */
/* If we need to re-order, then create a new array with the correct order and add it */
/* No re-order required, sever got it "right" - just straight add */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Options (features) HTML
		 */
/*
		 * Function: _fnAddOptionsHtml
		 * Purpose:  Add the options to the page HTML for the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
			 * Create a temporary, empty, div which we can later on replace with what we have generated
			 * we do it this way to rendering the 'options' html offline - speed :-)
			 */
/* 
			 * All DataTables are wrapped in a div - this is not currently optional - backwards 
			 * compatability. It can be removed if you don't want it.
			 */
/* Track where we want to insert the option */
/* Substitute any constants in the dom string */
/* Loop over the user set positioning and place the elements as needed */
/* New container div */
/* Check to see if we should append a class name to the container */
/* Move along the position array */
/* End container div */
/* Length */
/* Filter */
/* pRocessing */
/* EllisLab edit - I hard coded the id rather than writing a new node
					*/
/* Table */
/* Info */
/* Pagination */
/* EllisLab edit - I hard coded it
					*/
/* Plug-in features */
/* Add to the 2D features array */
/* Built our DOM structure - replace the holding div with what we want */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Feature: Filtering
		 */
/*
		 * Function: _fnFeatureHtmlFilter
		 * Purpose:  Generate the node required for filtering text
		 * Returns:  node
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Update all other filter input elements for the new display */
/* Now do the filter */
/* Prevent default */
/*
		 * Function: _fnFilterComplete
		 * Purpose:  Filter the table using both the global filter and column based filtering
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           object:oSearch: search information
		 *           int:iForce - optional - force a research of the master array (1) or not (undefined or 0)
		 */
/* Filter on everything */
/* Now do the individual column filter */
/* Custom filtering */
/* Tell the draw function we have been filtering */
/* Redraw the table */
/* Rebuild search array 'offline' */
/*
		 * Function: _fnFilterCustom
		 * Purpose:  Apply custom filtering functions
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Check if we should use this row based on the filtering function */
/*
		 * Function: _fnFilterColumn
		 * Purpose:  Filter the table on a per-column basis
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           string:sInput - string to filter on
		 *           int:iColumn - column to filter
		 *           bool:bEscapeRegex - escape regex or not
		 */
/*
		 * Function: _fnFilter
		 * Purpose:  Filter the data table based on user input and draw the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           string:sInput - string to filter on
		 *           int:iForce - optional - force a research of the master array (1) or not (undefined or 0)
		 *           bool:bEscapeRegex - escape regex or not
		 */
/* Check if we are forcing or not - optional parameter */
/* Need to take account of custom filtering functions always */
/* Generate the regular expression to use. Something along the lines of:
			 * ^(?=.*?\bone\b)(?=.*?\btwo\b)(?=.*?\bthree\b).*$
			 */
/* case insensitive */
/*
			 * If the input is blank - we want the full data set
			 */
/*
				 * We are starting a new search or the new search string is smaller 
				 * then the old one (i.e. delete). Search from the master array
			 	 */
/* Nuke the old display array - we are going to rebuild it */
/* Force a rebuild of the search array */
/* Search through all records to populate the search array
					 * The the oSettings.aiDisplayMaster and asDataSearch arrays have 1 to 1 
					 * mapping
					 */
/* Using old search array - refine it - do it this way for speed
			  	 * Don't have to search the whole master array again
			 		 */
/* Search the current results */
/*
		 * Function: _fnBuildSearchArray
		 * Purpose:  Create an array which can be quickly search through
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           int:iMaster - use the master data array - optional
		 */
/* Clear out the old data */
/*
		 * Function: _fnDataToSearch
		 * Purpose:  Convert raw data into something that the user can search on
		 * Returns:  string: - search string
		 * Inputs:   string:sData - data to be modified
		 *           string:sType - data type
		 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Feature: Sorting
		 */
/*
	 	 * Function: _fnSort
		 * Purpose:  Change the order of the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           bool:bApplyClasses - optional - should we apply classes or not
		 * Notes:    We always sort the master array and then apply a filter again
		 *   if it is needed. This probably isn't optimal - but atm I can't think
		 *   of any other way which is (each has disadvantages). we want to sort aiDisplayMaster - 
		 *   but according to aoData[]._aData
		 */
/* No sorting required if server-side or no sorting array */
/* If there is a sorting data type, and a fuction belonging to it, then we need to
				 * get the data from the developer's function and apply it for this column
				 */
/* DataTables offers two different methods for doing the 2D array sorting over multiple
				 * columns. The first is to construct a function dynamically, and then evaluate and run
				 * the function, while the second has no need for evalulation, but is a little bit slower.
				 * This is used for environments which do not allow eval() for code execuation such as AIR
				 */
/* Dynamically created sorting function. Based on the information that we have, we can
					 * create a sorting function as if it were specifically written for this sort. Here we
					 * want to build a function something like (for two column sorting):
					 *  fnLocalSorting = function(a,b){
					 *  	var iTest;
					 *  	iTest = oSort['string-asc']('data11', 'data12');
					 *  	if (iTest === 0)
					 *  		iTest = oSort['numeric-desc']('data21', 'data22');
					 *  		if (iTest === 0)
					 *  			return oSort['numeric-desc'](1,2);
					 *  	return iTest;
					 *  }
					 * So basically we have a test for each column, and if that column matches, test the
					 * next one. If all columns match, then we use a numeric sort on the position the two
					 * row have in the original data array in order to provide a stable sort.
					 */
/* The eval has to be done to a variable for IE */
/*
					 * Non-eval() sorting (AIR and other environments which doesn't allow code in eval()
					 * Note that for reasonable sized data sets this method is around 1.5 times slower than
					 * the eval above (hence why it is not used all the time). Oddly enough, it is ever so
					 * slightly faster for very small sets (presumably the eval has overhead).
					 *   Single column (1083 records) - eval: 32mS   AIR: 38mS
					 *   Two columns (1083 records) -   eval: 55mS   AIR: 66mS
					 */
/* Build a cached array so the sort doesn't have to process this stuff on every call */
/* Alter the sorting classes to take account of the changes */
/* Tell the draw function that we have sorted the data */
/* Copy the master data into the draw array and re-draw */
/* _fnFilter() will redraw the table for us */
/* reset display back to page 0 */
/*
		 * Function: _fnSortAttachListener
		 * Purpose:  Attach a sort handler (click) to a node
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           node:nNode - node to attach the handler to
		 *           int:iDataIndex - column sorting index
		 *           function:fnCallback - callback function - optional
		 */
/* If the column is not sortable - don't to anything */
/*
				 * This is a little bit odd I admit... I declare a temporary function inside the scope of
				 * _fnDrawHead and the click handler in order that the code presented here can be used 
				 * twice - once for when bProcessing is enabled, and another time for when it is 
				 * disabled, as we need to perform slightly different actions.
				 *   Basically the issue here is that the Javascript engine in modern browsers don't 
				 * appear to allow the rendering engine to update the display while it is still excuting
				 * it's thread (well - it does but only after long intervals). This means that the 
				 * 'processing' display doesn't appear for a table sort. To break the js thread up a bit
				 * I force an execution break by using setTimeout - but this breaks the expected 
				 * thread continuation for the end-developer's point of view (their code would execute
				 * too early), so we on;y do it when we absolutely have to.
				 */
/* If the shift key is pressed then we are multipe column sorting */
/* Are we already doing some kind of sort on this column? */
/* Reached the end of the sorting options, remove from multi-col sort */
/* Move onto next sorting direction */
/* No sort yet - add it in */
/* If no shift key then single column sort */
/* Run the sort */
/* /fnInnerSorting */
/* Call the user specified callback function - used for async user interaction */
/*
		 * Function: _fnSortingClasses
		 * Purpose:  Set the sortting classes on the header
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 * Notes:    It is safe to call this function when bSort is false
		 */
/* Apply the required classes to the header */
/* jQuery UI uses extra markup */
/* No sorting on this column, so add the base class. This will have been assigned by
					 * _fnAddColumn
					 */
/* 
			 * Apply the required classes to the table body
			 * Note that this is given as a feature switch since it can significantly slow down a sort
			 * on large data sets (adding and removing of classes is always slow at the best of times..)
			 * Further to this, note that this code is admitadly fairly ugly. It could be made a lot 
			 * simpiler using jQuery selectors and add/removeClass, but that is significantly slower
			 * (on the order of 5 times slower) - hence the direct DOM manipulation here.
			 */
/* Remove the old classes */
/* Add the new classes to the table */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Feature: Pagination. Note that most of the paging logic is done in 
		 * _oExt.oPagination
		 */
/*
		 * Function: _fnFeatureHtmlPaginate
		 * Purpose:  Generate the node required for default pagination
		 * Returns:  node
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Add a draw callback for the pagination on first instance, to update the paging display */
/*
		 * Function: _fnPageChange
		 * Purpose:  Alter the display settings to change the page
		 * Returns:  bool:true - page has changed, false - no change (no effect) eg 'first' on page 1
		 * Inputs:   object:oSettings - dataTables settings object
		 *           string:sAction - paging action to take: "first", "previous", "next" or "last"
		 */
/* Correct for underrun */
/* Make sure we are not over running the display array */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Feature: HTML info
		 */
/*
		 * Function: _fnFeatureHtmlInfo
		 * Purpose:  Generate the node required for the info display
		 * Returns:  node
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Actions that are to be taken once only for this feature */
/* Add draw callback */
/* Add id */
/*
		 * Function: _fnUpdateInfo
		 * Purpose:  Update the information elements in the display
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Show information about the table */
/* Empty record set */
/* Rmpty record set after filtering */
/* Normal record set */
/* Record set after filtering */
/* No point in recalculating for the other info elements, just copy the first one in */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Feature: Length change
		 */
/*
		 * Function: _fnFeatureHtmlLength
		 * Purpose:  Generate the node required for user display length changing
		 * Returns:  node
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* This can be overruled by not using the _MENU_ var/macro in the language variable */
/*
			 * Set the length to the current display length - thanks to Andrea Pavlovic for this fix,
			 * and Stefan Skopnik for fixing the fix!
			 */
/* Update all other length options for the new display */
/* Redraw the table */
/* If we have space to show extra rows (backing up from the end point - then do so */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Feature: Processing incidator
		 */
/*
		 * Function: _fnFeatureHtmlProcessing
		 * Purpose:  Generate the node required for the processing node
		 * Returns:  node
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnProcessingDisplay
		 * Purpose:  Display or hide the processing indicator
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           bool:
		 *   true - show the processing indicator
		 *   false - don't show
		 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Support functions
		 */
/*
		 * Function: _fnVisibleToColumnIndex
		 * Purpose:  Covert the index of a visible column to the index in the data array (take account
		 *   of hidden columns)
		 * Returns:  int:i - the data index
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnColumnIndexToVisible
		 * Purpose:  Covert the index of an index in the data array and convert it to the visible
		 *   column index (take account of hidden columns)
		 * Returns:  int:i - the data index
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnNodeToDataIndex
		 * Purpose:  Take a TR element and convert it to an index in aoData
		 * Returns:  int:i - index if found, null if not
		 * Inputs:   object:s - dataTables settings object
		 *           node:n - the TR element to find
		 */
/*
		 * Function: _fnVisbleColumns
		 * Purpose:  Get the number of visible columns
		 * Returns:  int:i - the number of visible columns
		 * Inputs:   object:oS - dataTables settings object
		 */
/*
		 * Function: _fnCalculateEnd
		 * Purpose:  Rcalculate the end point based on the start point
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Set the end point of the display - based on how many elements there are
				 * still to display
				 */
/*
		 * Function: _fnConvertToWidth
		 * Purpose:  Convert a CSS unit width to pixels (e.g. 2em)
		 * Returns:  int:iWidth - width in pixels
		 * Inputs:   string:sWidth - width to be converted
		 *           node:nParent - parent to get the with for (required for
		 *             relative widths) - optional
		 */
/*
		 * Function: _fnCalculateColumnWidths
		 * Purpose:  Calculate the width of columns for the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Convert any user input sizes into pixel sizes */
/* Total up the user defined widths for later calculations */
/* If the number of columns in the DOM equals the number that we
			 * have to process in dataTables, then we can use the offsets that are
			 * created by the web-browser. No custom sizes can be set in order for
			 * this to happen
			 */
/* Otherwise we are going to have to do some calculations to get
				 * the width of each column. Construct a 1 row table with the maximum
				 * string sizes in the data, and any user defined widths
				 */
/* Construct a tempory table which we will inject (invisibly) into
				 * the dom - to let the browser do all the hard word
				 */
/* Create the tmp table node (thank you jQuery) */
/* Try to aviod scroll bar */
/* Gather in the browser calculated widths for the rows */
/*
		 * Function: fnGetMaxLenString
		 * Purpose:  Get the maximum strlen for each data column
		 * Returns:  string: - max strlens for each column
		 * Inputs:   object:oSettings - dataTables settings object
		 *           int:iCol - column of interest
		 */
/*
		 * Function: _fnArrayCmp
		 * Purpose:  Compare two arrays
		 * Returns:  0 if match, 1 if length is different, 2 if no match
		 * Inputs:   array:aArray1 - first array
		 *           array:aArray2 - second array
		 */
/*
		 * Function: _fnDetectType
		 * Purpose:  Get the sort type based on an input string
		 * Returns:  string: - type (defaults to 'string' if no type can be detected)
		 * Inputs:   string:sData - data we wish to know the type of
		 * Notes:    This function makes use of the DataTables plugin objct _oExt 
		 *   (.aTypes) such that new types can easily be added.
		 */
/*
		 * Function: _fnSettingsFromNode
		 * Purpose:  Return the settings object for a particular table
		 * Returns:  object: Settings object - or null if not found
		 * Inputs:   node:nTable - table we are using as a dataTable
		 */
/*
		 * Function: _fnGetDataMaster
		 * Purpose:  Return an array with the full table data
		 * Returns:  array array:aData - Master data array
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnGetTrNodes
		 * Purpose:  Return an array with the TR nodes for the table
		 * Returns:  array: - TR array
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnGetTdNodes
		 * Purpose:  Return an array with the TD nodes for the table
		 * Returns:  array: - TD array
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnEscapeRegex
		 * Purpose:  scape a string stuch that it can be used in a regular expression
		 * Returns:  string: - escaped string
		 * Inputs:   string:sVal - string to escape
		 */
/*
		 * Function: _fnReOrderIndex
		 * Purpose:  Figure out how to reorder a display list
		 * Returns:  array int:aiReturn - index list for reordering
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnColumnOrdering
		 * Purpose:  Get the column ordering that DataTables expects
		 * Returns:  string: - comma separated list of names
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnClearTable
		 * Purpose:  Nuke the table
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/*
		 * Function: _fnSaveState
		 * Purpose:  Save the state of a table in a cookie such that the page can be reloaded
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 */
/* Store the interesting variables */
/*
		 * Function: _fnLoadState
		 * Purpose:  Attempt to load a saved table state from a cookie
		 * Returns:  -
		 * Inputs:   object:oSettings - dataTables settings object
		 *           object:oInit - DataTables init object so we can override settings
		 */
/* Try/catch the JSON eval - if it is bad then we ignore it */
/* Use the JSON library for safety - if it is available */
/* DT 1.4.0 used single quotes for a string - JSON.parse doesn't allow this and throws
						 * an error. So for now we can do this. This can be removed in future it is just to
						 * allow the tranfrer to 1.4.1+ to occur
						 */
/* Restore key features */
/* Search filtering - global reference added in 1.4.1 */
/* Column filtering - added in 1.5.0 beta 6 */
/* Column visibility state - added in 1.5.0 beta 10 */
/* Pass back visibiliy settings to the init handler, but to do not here override
					 * the init object that the user might have passed in
					 */
/*
		 * Function: _fnCreateCookie
		 * Purpose:  Create a new cookie with a value to store the state of a table
		 * Returns:  -
		 * Inputs:   string:sName - name of the cookie to create
		 *           string:sValue - the value the cookie should take
		 *           int:iSecs - duration of the cookie
		 */
/* 
			 * Shocking but true - it would appear IE has major issues with having the path being
			 * set to anything but root. We need the cookie to be available based on the path, so we
			 * have to append the pathname to the cookie name. Appalling.
			 */
/*
		 * Function: _fnReadCookie
		 * Purpose:  Read an old cookie to get a cookie with an old table state
		 * Returns:  string: - contents of the cookie - or null if no cookie with that name found
		 * Inputs:   string:sName - name of the cookie to read
		 */
/*
		 * Function: _fnGetUniqueThs
		 * Purpose:  Get an array of unique th elements, one for each column
		 * Returns:  array node:aReturn - list of unique ths
		 * Inputs:   node:nThead - The thead element for the table
		 */
/* Nice simple case */
/* Otherwise we need to figure out the layout array to get the nodes */
/* Calculate a layout array */
/* Convert the layout array into a node array
			 * Note the use of aLayout[0] in the outloop, we want the outer loop to occur the same
			 * number of times as there are columns. Unusual having nested loops this way around
			 * but is what we need here.
			 */
/*
		 * Function: _fnMap
		 * Purpose:  See if a property is defined on one object, if so assign it to the other object
		 * Returns:  - (done by reference)
		 * Inputs:   object:oRet - target object
		 *           object:oSrc - source object
		 *           string:sName - property
		 *           string:sMappedName - name to map too - optional, sName used if not given
		 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - API
		 * 
		 * I'm not overly happy with this solution - I'd much rather that there was a way of getting
		 * a list of all the private functions and do what we need to dynamically - but that doesn't
		 * appear to be possible. Bonkers. A better solution would be to provide a 'bind' type object
		 * To do - bind type method in DTs 2.x.
		 */
/* Want to be able to reference "this" inside the this.each function */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Section - Constructor
		 */
/* Sanity check that we are not re-initialising a table - if we are, alert an error */
/* Make a complete and independent copy of the settings object */
/* Set the id */
/* Set the table node */
/* Bind the API functions to the settings, so we can perform actions whenever oSettings is
			 * available
			 */
/* Store the features that we have available */
/* Add user given callback function to array */
/* Enable sort classes for server-side processing. Safe to do it here, since server-side
					 * processing must be enabled by the developer
					 */
/* Use the JUI classes object for display. You could clone the oStdClasses object if 
					 * you want to have multiple tables with multiple independent classes 
					 */
/* Set the DOM to use a layout suitable for jQuery UI's theming */
/* Display start point, taking into account the save saving */
/* Must be done after everything which can be overridden by a cookie! */
/* Backwards compatability */
/* aoColumns / aoData - remove at some point... */
/* Language definitions */
/* Get the language definitions from a file */
/* Warning: The _fnLanguageProcess function is async to the remainder of this function due
				 * to the XHR. We use _bInitialised in _fnLanguageProcess() to check this the processing 
				 * below is complete. The reason for spliting it like this is optimisation - we can fire
				 * off the XHR (if needed) and then continue processing the data.
				 */
/* Create a dummy object for quick manipulation later on. */
/* Add the strip classes now that we know which classes to apply - unless overruled */
/* See if we should load columns automatically or use defined ones - a bit messy this... */
/* Check if we have column visibilty state to restore, and also that the length of the 
				 * state saved columns matches the currently know number of columns
				 */
/* Check the aaSorting array */
/* Add a default sorting index */
/* If aaSorting is not defined, then we use the first indicator in asSorting */
/* Set the current sorting index based on aoColumns.asSorting */
/* Sanity check that there is a thead and tfoot. If not let's just create them */
/* Check if there is data passing into the constructor */
/* Grab the data from the page */
/* Copy the data index array */
/* Calculate sizes for columns */
/* Initialisation complete - table can be drawn */
/* Check if we need to initialise the table (it might not have been handed off to the
			 * language processor)
			 */

(function(m){m.fn.dataTableSettings=[];var F=m.fn.dataTableSettings;m.fn.dataTableExt={};var n=m.fn.dataTableExt;n.sVersion="1.6.2";n.iApiIndex=0;n.oApi={};n.afnFiltering=[];n.aoFeatures=[];n.ofnSearch={};n.afnSortData=[];n.oStdClasses={sPagePrevEnabled:"paginate_enabled_previous",sPagePrevDisabled:"paginate_disabled_previous",sPageNextEnabled:"paginate_enabled_next",sPageNextDisabled:"paginate_disabled_next",sPageJUINext:"",sPageJUIPrev:"",sPageButton:"paginate_button",sPageButtonActive:"paginate_active",
sPageButtonStaticDisabled:"paginate_button",sPageFirst:"first",sPagePrevious:"previous",sPageNext:"next",sPageLast:"last",sStripOdd:"odd",sStripEven:"even",sRowEmpty:"dataTables_empty",sWrapper:"dataTables_wrapper",sFilter:"dataTables_filter",sInfo:"dataTables_info",sPaging:"dataTables_paginate paging_",sLength:"dataTables_length",sProcessing:"dataTables_processing",sSortAsc:"sorting_asc",sSortDesc:"sorting_desc",sSortable:"sorting",sSortableAsc:"sorting_asc_disabled",sSortableDesc:"sorting_desc_disabled",
sSortableNone:"sorting_disabled",sSortColumn:"sorting_",sSortJUIAsc:"",sSortJUIDesc:"",sSortJUI:"",sSortJUIAscAllowed:"",sSortJUIDescAllowed:""};n.oJUIClasses={sPagePrevEnabled:"fg-button ui-state-default ui-corner-left",sPagePrevDisabled:"fg-button ui-state-default ui-corner-left ui-state-disabled",sPageNextEnabled:"fg-button ui-state-default ui-corner-right",sPageNextDisabled:"fg-button ui-state-default ui-corner-right ui-state-disabled",sPageJUINext:"ui-icon ui-icon-circle-arrow-e",sPageJUIPrev:"ui-icon ui-icon-circle-arrow-w",
sPageButton:"fg-button ui-state-default",sPageButtonActive:"fg-button ui-state-default ui-state-disabled",sPageButtonStaticDisabled:"fg-button ui-state-default ui-state-disabled",sPageFirst:"first ui-corner-tl ui-corner-bl",sPagePrevious:"previous",sPageNext:"next",sPageLast:"last ui-corner-tr ui-corner-br",sStripOdd:"odd",sStripEven:"even",sRowEmpty:"dataTables_empty",sWrapper:"dataTables_wrapper",sFilter:"dataTables_filter",sInfo:"dataTables_info",sPaging:"dataTables_paginate fg-buttonset fg-buttonset-multi paging_",
sLength:"dataTables_length",sProcessing:"dataTables_processing",sSortAsc:"ui-state-default",sSortDesc:"ui-state-default",sSortable:"ui-state-default",sSortableAsc:"ui-state-default",sSortableDesc:"ui-state-default",sSortableNone:"ui-state-default",sSortColumn:"sorting_",sSortJUIAsc:"css_right ui-icon ui-icon-triangle-1-n",sSortJUIDesc:"css_right ui-icon ui-icon-triangle-1-s",sSortJUI:"css_right ui-icon ui-icon-carat-2-n-s",sSortJUIAscAllowed:"css_right ui-icon ui-icon-carat-1-n",sSortJUIDescAllowed:"css_right ui-icon ui-icon-carat-1-s"};
n.oPagination={two_button:{fnInit:function(g,q,k){var l,s,w;if(g.bJUI){l=document.createElement("a");s=document.createElement("a");w=document.createElement("span");w.className=g.oClasses.sPageJUINext;s.appendChild(w);w=document.createElement("span");w.className=g.oClasses.sPageJUIPrev;l.appendChild(w)}else{l=document.createElement("div");s=document.createElement("div")}l.className=g.oClasses.sPagePrevDisabled;s.className=g.oClasses.sPageNextDisabled;l.title=g.oLanguage.oPaginate.sPrevious;s.title=
g.oLanguage.oPaginate.sNext;q.appendChild(l);q.appendChild(s);m(l).click(function(){g.oApi._fnPageChange(g,"previous")&&k(g)});m(s).click(function(){g.oApi._fnPageChange(g,"next")&&k(g)});m(l).bind("selectstart",function(){return false});m(s).bind("selectstart",function(){return false});if(g.sTableId!==""&&typeof g.aanFeatures.p=="undefined"){q.setAttribute("id",g.sTableId+"_paginate");l.setAttribute("id",g.sTableId+"_previous");s.setAttribute("id",g.sTableId+"_next")}},fnUpdate:function(g){if(g.aanFeatures.p)for(var q=
g.aanFeatures.p,k=0,l=q.length;k<l;k++)if(q[k].childNodes.length!==0){q[k].childNodes[0].className=g._iDisplayStart===0?g.oClasses.sPagePrevDisabled:g.oClasses.sPagePrevEnabled;q[k].childNodes[1].className=g.fnDisplayEnd()==g.fnRecordsDisplay()?g.oClasses.sPageNextDisabled:g.oClasses.sPageNextEnabled}}},iFullNumbersShowPages:5,full_numbers:{fnInit:function(g,q,k){var l=document.createElement("span"),s=document.createElement("span"),w=document.createElement("span"),A=document.createElement("span"),
y=document.createElement("span");l.innerHTML=g.oLanguage.oPaginate.sFirst;s.innerHTML=g.oLanguage.oPaginate.sPrevious;A.innerHTML=g.oLanguage.oPaginate.sNext;y.innerHTML=g.oLanguage.oPaginate.sLast;var u=g.oClasses;l.className=u.sPageButton+" "+u.sPageFirst;s.className=u.sPageButton+" "+u.sPagePrevious;A.className=u.sPageButton+" "+u.sPageNext;y.className=u.sPageButton+" "+u.sPageLast;q.appendChild(l);q.appendChild(s);q.appendChild(w);q.appendChild(A);q.appendChild(y);m(l).click(function(){g.oApi._fnPageChange(g,
"first")&&k(g)});m(s).click(function(){g.oApi._fnPageChange(g,"previous")&&k(g)});m(A).click(function(){g.oApi._fnPageChange(g,"next")&&k(g)});m(y).click(function(){g.oApi._fnPageChange(g,"last")&&k(g)});m("span",q).bind("mousedown",function(){return false}).bind("selectstart",function(){return false});if(g.sTableId!==""&&typeof g.aanFeatures.p=="undefined"){q.setAttribute("id",g.sTableId+"_paginate");l.setAttribute("id",g.sTableId+"_first");s.setAttribute("id",g.sTableId+"_previous");A.setAttribute("id",
g.sTableId+"_next");y.setAttribute("id",g.sTableId+"_last")}},fnUpdate:function(g,q){if(g.aanFeatures.p){var k=n.oPagination.iFullNumbersShowPages,l=Math.floor(k/2),s=Math.ceil(g.fnRecordsDisplay()/g._iDisplayLength),w=Math.ceil(g._iDisplayStart/g._iDisplayLength)+1,A="",y,u=g.oClasses;if(s<k){l=1;y=s}else if(w<=l){l=1;y=k}else if(w>=s-l){l=s-k+1;y=s}else{l=w-Math.ceil(k/2)+1;y=l+k-1}for(k=l;k<=y;k++)A+=w!=k?'<span class="'+u.sPageButton+'">'+k+"</span>":'<span class="'+u.sPageButtonActive+'">'+k+
"</span>";y=g.aanFeatures.p;var r,H=function(){g._iDisplayStart=(this.innerHTML*1-1)*g._iDisplayLength;q(g);return false},M=function(){return false};k=0;for(l=y.length;k<l;k++)if(y[k].childNodes.length!==0){r=y[k].childNodes[2];r.innerHTML=A;m("span",r).click(H).bind("mousedown",M).bind("selectstart",M);r=y[k].getElementsByTagName("span");r=[r[0],r[1],r[r.length-2],r[r.length-1]];m(r).removeClass(u.sPageButton+" "+u.sPageButtonActive+" "+u.sPageButtonStaticDisabled);if(w==1){r[0].className+=" "+u.sPageButtonStaticDisabled;
r[1].className+=" "+u.sPageButtonStaticDisabled}else{r[0].className+=" "+u.sPageButton;r[1].className+=" "+u.sPageButton}if(s===0||w==s||g._iDisplayLength==-1){r[2].className+=" "+u.sPageButtonStaticDisabled;r[3].className+=" "+u.sPageButtonStaticDisabled}else{r[2].className+=" "+u.sPageButton;r[3].className+=" "+u.sPageButton}m(r[0])[w<4?"hide":"show"]();m(r[1])[w==1?"hide":"show"]();m(r[2])[w==s?"hide":"show"]();m(r[3])[w>s-3?"hide":"show"]()}}}}};n.oSort={"string-asc":function(g,q){var k=g.toLowerCase(),
l=q.toLowerCase();return k<l?-1:k>l?1:0},"string-desc":function(g,q){var k=g.toLowerCase(),l=q.toLowerCase();return k<l?1:k>l?-1:0},"html-asc":function(g,q){var k=g.replace(/<.*?>/g,"").toLowerCase(),l=q.replace(/<.*?>/g,"").toLowerCase();return k<l?-1:k>l?1:0},"html-desc":function(g,q){var k=g.replace(/<.*?>/g,"").toLowerCase(),l=q.replace(/<.*?>/g,"").toLowerCase();return k<l?1:k>l?-1:0},"date-asc":function(g,q){var k=Date.parse(g),l=Date.parse(q);if(isNaN(k))k=Date.parse("01/01/1970 00:00:00");
if(isNaN(l))l=Date.parse("01/01/1970 00:00:00");return k-l},"date-desc":function(g,q){var k=Date.parse(g),l=Date.parse(q);if(isNaN(k))k=Date.parse("01/01/1970 00:00:00");if(isNaN(l))l=Date.parse("01/01/1970 00:00:00");return l-k},"numeric-asc":function(g,q){return(g=="-"?0:g)-(q=="-"?0:q)},"numeric-desc":function(g,q){return(q=="-"?0:q)-(g=="-"?0:g)}};n.aTypes=[function(g){if(typeof g=="number")return"numeric";else if(typeof g.charAt!="function")return null;var q,k=false;q=g.charAt(0);if("0123456789-".indexOf(q)==
-1)return null;for(var l=1;l<g.length;l++){q=g.charAt(l);if("0123456789.".indexOf(q)==-1)return null;if(q=="."){if(k)return null;k=true}}return"numeric"},function(g){g=Date.parse(g);if(g!==null&&!isNaN(g))return"date";return null}];n._oExternConfig={iNextUnique:0};m.fn.dataTable=function(g){function q(){this.fnRecordsTotal=function(){return this.oFeatures.bServerSide?this._iRecordsTotal:this.aiDisplayMaster.length};this.fnRecordsDisplay=function(){return this.oFeatures.bServerSide?this._iRecordsDisplay:
this.aiDisplay.length};this.fnDisplayEnd=function(){return this.oFeatures.bServerSide?this._iDisplayStart+this.aiDisplay.length:this._iDisplayEnd};this.sInstance=null;this.oFeatures={bPaginate:true,bLengthChange:true,bFilter:true,bSort:true,bInfo:true,bAutoWidth:true,bProcessing:false,bSortClasses:true,bStateSave:false,bServerSide:false};this.aanFeatures=[];this.oLanguage={sProcessing:"Processing...",sLengthMenu:"Show _MENU_ entries",sZeroRecords:"No matching records found",sInfo:"Showing _START_ to _END_ of _TOTAL_ entries",
sInfoEmpty:"Showing 0 to 0 of 0 entries",sInfoFiltered:"(filtered from _MAX_ total entries)",sInfoPostFix:"",sSearch:"Search:",sUrl:"",oPaginate:{sFirst:"First",sPrevious:"Previous",sNext:"Next",sLast:"Last"}};this.aoData=[];this.aiDisplay=[];this.aiDisplayMaster=[];this.aoColumns=[];this.iNextId=0;this.asDataSearch=[];this.oPreviousSearch={sSearch:"",bEscapeRegex:true};this.aoPreSearchCols=[];this.aaSorting=[[0,"asc",0]];this.aaSortingFixed=null;this.asStripClasses=[];this.fnFooterCallback=this.fnHeaderCallback=
this.fnRowCallback=null;this.aoDrawCallback=[];this.fnInitComplete=null;this.sTableId="";this.nTable=null;this.iDefaultSortIndex=0;this.bInitialised=false;this.aoOpenRows=[];this.sDom="lfrtip";this.sPaginationType="two_button";this.iCookieDuration=7200;this.sAjaxSource=null;this.bAjaxDataGet=true;this.fnServerData=m.getJSON;this.iServerDraw=0;this._iDisplayLength=10;this._iDisplayStart=0;this._iDisplayEnd=10;this._iRecordsDisplay=this._iRecordsTotal=0;this.bJUI=false;this.oClasses=n.oStdClasses;this.bSorted=
this.bFiltered=false}function k(a){return function(){var b=[z(this[n.iApiIndex])].concat(Array.prototype.slice.call(arguments));return n.oApi[a].apply(this,b)}}function l(a){if(a.bInitialised===false)setTimeout(function(){l(a)},200);else{ca(a);u(a);if(a.oFeatures.bSort){N(a,false);O(a)}else{a.aiDisplay=a.aiDisplayMaster.slice();B(a);r(a)}if(a.sAjaxSource!==null&&!a.oFeatures.bServerSide){D(a,true);a.fnServerData(a.sAjaxSource,null,function(b){for(var c=0;c<b.aaData.length;c++)A(a,b.aaData[c]);a.iInitDisplayStart=
a._iDisplayStart;if(a.oFeatures.bSort)N(a);else{a.aiDisplay=a.aiDisplayMaster.slice();B(a);r(a)}D(a,false);typeof a.fnInitComplete=="function"&&a.fnInitComplete(a,b)})}else{typeof a.fnInitComplete=="function"&&a.fnInitComplete(a);a.oFeatures.bServerSide||D(a,false)}}}function s(a,b,c){o(a.oLanguage,b,"sProcessing");o(a.oLanguage,b,"sLengthMenu");o(a.oLanguage,b,"sZeroRecords");o(a.oLanguage,b,"sInfo");o(a.oLanguage,b,"sInfoEmpty");o(a.oLanguage,b,"sInfoFiltered");o(a.oLanguage,b,"sInfoPostFix");o(a.oLanguage,
b,"sSearch");if(typeof b.oPaginate!="undefined"){o(a.oLanguage.oPaginate,b.oPaginate,"sFirst");o(a.oLanguage.oPaginate,b.oPaginate,"sPrevious");o(a.oLanguage.oPaginate,b.oPaginate,"sNext");o(a.oLanguage.oPaginate,b.oPaginate,"sLast")}c&&l(a)}function w(a,b,c){a.aoColumns[a.aoColumns.length++]={sType:null,_bAutoType:true,bVisible:true,bSearchable:true,bSortable:true,asSorting:["asc","desc"],sSortingClass:a.oClasses.sSortable,sSortingClassJUI:a.oClasses.sSortJUI,sTitle:c?c.innerHTML:"",sName:"",sWidth:null,
sClass:null,fnRender:null,bUseRendered:true,iDataSort:a.aoColumns.length-1,sSortDataType:"std",nTh:c?c:document.createElement("th"),nTf:null};c=a.aoColumns.length-1;var d=a.aoColumns[c];if(typeof b!="undefined"&&b!==null){if(typeof b.sType!="undefined"){d.sType=b.sType;d._bAutoType=false}o(d,b,"bVisible");o(d,b,"bSearchable");o(d,b,"bSortable");o(d,b,"sTitle");o(d,b,"sName");o(d,b,"sWidth");o(d,b,"sClass");o(d,b,"fnRender");o(d,b,"bUseRendered");o(d,b,"iDataSort");o(d,b,"asSorting");o(d,b,"sSortDataType")}if(!a.oFeatures.bSort)d.bSortable=
false;if(!d.bSortable||m.inArray("asc",d.asSorting)==-1&&m.inArray("desc",d.asSorting)==-1){d.sSortingClass=a.oClasses.sSortableNone;d.sSortingClassJUI=""}else if(m.inArray("asc",d.asSorting)!=-1&&m.inArray("desc",d.asSorting)==-1){d.sSortingClass=a.oClasses.sSortableAsc;d.sSortingClassJUI=a.oClasses.sSortJUIAscAllowed}else if(m.inArray("asc",d.asSorting)==-1&&m.inArray("desc",d.asSorting)!=-1){d.sSortingClass=a.oClasses.sSortableDesc;d.sSortingClassJUI=a.oClasses.sSortJUIDescAllowed}if(typeof a.aoPreSearchCols[c]==
"undefined"||a.aoPreSearchCols[c]===null)a.aoPreSearchCols[c]={sSearch:"",bEscapeRegex:true};else if(typeof a.aoPreSearchCols[c].bEscapeRegex=="undefined")a.aoPreSearchCols[c].bEscapeRegex=true}function A(a,b){if(b.length!=a.aoColumns.length){alert("DataTables warning: Added data does not match known number of columns");return-1}var c=a.aoData.length;a.aoData.push({nTr:document.createElement("tr"),_iId:a.iNextId++,_aData:b.slice(),_anHidden:[],_sRowStripe:""});for(var d,e,f=0;f<b.length;f++){d=document.createElement("td");
if(typeof a.aoColumns[f].fnRender=="function"){e=a.aoColumns[f].fnRender({iDataRow:c,iDataColumn:f,aData:b,oSettings:a});d.innerHTML=e;if(a.aoColumns[f].bUseRendered)a.aoData[c]._aData[f]=e}else d.innerHTML=b[f];if(a.aoColumns[f].sClass!==null)d.className=a.aoColumns[f].sClass;if(a.aoColumns[f]._bAutoType&&a.aoColumns[f].sType!="string"){e=U(a.aoData[c]._aData[f]);if(a.aoColumns[f].sType===null)a.aoColumns[f].sType=e;else if(a.aoColumns[f].sType!=e)a.aoColumns[f].sType="string"}if(a.aoColumns[f].bVisible)a.aoData[c].nTr.appendChild(d);
else a.aoData[c]._anHidden[f]=d}a.aiDisplayMaster.push(c);return c}function y(a){var b,c,d,e,f,h,i,j;if(a.sAjaxSource===null){i=a.nTable.getElementsByTagName("tbody")[0].childNodes;b=0;for(c=i.length;b<c;b++)if(i[b].nodeName=="TR"){h=a.aoData.length;a.aoData.push({nTr:i[b],_iId:a.iNextId++,_aData:[],_anHidden:[],_sRowStripe:""});a.aiDisplayMaster.push(h);j=a.aoData[h]._aData;h=i[b].childNodes;d=f=0;for(e=h.length;d<e;d++)if(h[d].nodeName=="TD"){j[f]=h[d].innerHTML;f++}}}i=P(a);h=[];b=0;for(c=i.length;b<
c;b++){d=0;for(e=i[b].childNodes.length;d<e;d++){f=i[b].childNodes[d];f.nodeName=="TD"&&h.push(f)}}h.length!=i.length*a.aoColumns.length&&alert("DataTables warning: Unexpected number of TD elements. Expected "+i.length*a.aoColumns.length+" and got "+h.length+". DataTables does not support rowspan / colspan in the table body, and there must be one cell for each row/column combination.");i=0;for(d=a.aoColumns.length;i<d;i++){if(a.aoColumns[i].sTitle===null)a.aoColumns[i].sTitle=a.aoColumns[i].nTh.innerHTML;
e=a.aoColumns[i]._bAutoType;f=typeof a.aoColumns[i].fnRender=="function";j=a.aoColumns[i].sClass!==null;var p=a.aoColumns[i].bVisible,t,v;if(e||f||j||!p){b=0;for(c=a.aoData.length;b<c;b++){t=h[b*d+i];if(e)if(a.aoColumns[i].sType!="string"){v=U(a.aoData[b]._aData[i]);if(a.aoColumns[i].sType===null)a.aoColumns[i].sType=v;else if(a.aoColumns[i].sType!=v)a.aoColumns[i].sType="string"}if(f){v=a.aoColumns[i].fnRender({iDataRow:b,iDataColumn:i,aData:a.aoData[b]._aData,oSettings:a});t.innerHTML=v;if(a.aoColumns[i].bUseRendered)a.aoData[b]._aData[i]=
v}if(j)t.className+=" "+a.aoColumns[i].sClass;if(!p){a.aoData[b]._anHidden[i]=t;t.parentNode.removeChild(t)}}}}}function u(a){var b,c,d,e=0;if(a.nTable.getElementsByTagName("thead")[0].getElementsByTagName("th").length!==0){b=0;for(d=a.aoColumns.length;b<d;b++){c=a.aoColumns[b].nTh;if(a.aoColumns[b].bVisible){if(a.aoColumns[b].sWidth!==null)c.style.width=a.aoColumns[b].sWidth;if(a.aoColumns[b].sTitle!=c.innerHTML)c.innerHTML=a.aoColumns[b].sTitle}else{c.parentNode.removeChild(c);e++}}}else{e=document.createElement("tr");
b=0;for(d=a.aoColumns.length;b<d;b++){c=a.aoColumns[b].nTh;c.innerHTML=a.aoColumns[b].sTitle;if(a.aoColumns[b].bVisible){if(a.aoColumns[b].sClass!==null)c.className=a.aoColumns[b].sClass;if(a.aoColumns[b].sWidth!==null)c.style.width=a.aoColumns[b].sWidth;e.appendChild(c)}}m("thead:eq(0)",a.nTable).html("")[0].appendChild(e)}if(a.bJUI){b=0;for(d=a.aoColumns.length;b<d;b++)a.aoColumns[b].nTh.insertBefore(document.createElement("span"),a.aoColumns[b].nTh.firstChild)}if(a.oFeatures.bSort){for(b=0;b<a.aoColumns.length;b++)a.aoColumns[b].bSortable!==
false?da(a,a.aoColumns[b].nTh,b):m(a.aoColumns[b].nTh).addClass(a.oClasses.sSortableNone);m("thead:eq(0) th",a.nTable).mousedown(function(f){if(f.shiftKey){this.onselectstart=function(){return false};return false}})}b=a.nTable.getElementsByTagName("tfoot");if(b.length!==0){e=0;c=b[0].getElementsByTagName("th");b=0;for(d=c.length;b<d;b++){a.aoColumns[b].nTf=c[b-e];if(!a.aoColumns[b].bVisible){c[b-e].parentNode.removeChild(c[b-e]);e++}}}}function r(a){var b,c,d=[];b=0;var e=false;c=a.asStripClasses.length;
var f=a.aoOpenRows.length;if(!(a.oFeatures.bServerSide&&!M(a))){if(typeof a.iInitDisplayStart!="undefined"&&a.iInitDisplayStart!=-1){a._iDisplayStart=a.iInitDisplayStart>=a.fnRecordsDisplay()?0:a.iInitDisplayStart;a.iInitDisplayStart=-1;B(a)}if(a.aiDisplay.length!==0){var h=a._iDisplayStart,i=a._iDisplayEnd;if(a.oFeatures.bServerSide){h=0;i=a.aoData.length}for(h=h;h<i;h++){var j=a.aoData[a.aiDisplay[h]],p=j.nTr;if(c!==0){var t=a.asStripClasses[b%c];if(j._sRowStripe!=t){m(p).removeClass(j._sRowStripe).addClass(t);
j._sRowStripe=t}}if(typeof a.fnRowCallback=="function"){p=a.fnRowCallback(p,a.aoData[a.aiDisplay[h]]._aData,b,h);if(!p&&!e){alert("DataTables warning: A node was not returned by fnRowCallback");e=true}}d.push(p);b++;if(f!==0)for(j=0;j<f;j++)p==a.aoOpenRows[j].nParent&&d.push(a.aoOpenRows[j].nTr)}}else{d[0]=document.createElement("tr");if(typeof a.asStripClasses[0]!="undefined")d[0].className=a.asStripClasses[0];e=document.createElement("td");e.setAttribute("valign","top");e.colSpan=a.aoColumns.length;
e.className=a.oClasses.sRowEmpty;e.innerHTML=a.oLanguage.sZeroRecords;d[b].appendChild(e)}typeof a.fnHeaderCallback=="function"&&a.fnHeaderCallback(m("thead:eq(0)>tr",a.nTable)[0],Q(a),a._iDisplayStart,a.fnDisplayEnd(),a.aiDisplay);typeof a.fnFooterCallback=="function"&&a.fnFooterCallback(m("tfoot:eq(0)>tr",a.nTable)[0],Q(a),a._iDisplayStart,a.fnDisplayEnd(),a.aiDisplay);e=a.nTable.getElementsByTagName("tbody");if(e[0]){c=e[0].childNodes;for(b=c.length-1;b>=0;b--)c[b].parentNode.removeChild(c[b]);
b=0;for(c=d.length;b<c;b++)e[0].appendChild(d[b])}b=0;for(c=a.aoDrawCallback.length;b<c;b++)a.aoDrawCallback[b].fn(a);a.bSorted=false;a.bFiltered=false;if(typeof a._bInitComplete=="undefined"){a._bInitComplete=true;if(a.oFeatures.bAutoWidth&&a.nTable.offsetWidth!==0)a.nTable.style.width=a.nTable.offsetWidth+"px"}}}function H(a){if(a.oFeatures.bSort)N(a,a.oPreviousSearch);else if(a.oFeatures.bFilter)I(a,a.oPreviousSearch);else{B(a);r(a)}}function M(a){if(a.bAjaxDataGet){D(a,true);var b=a.aoColumns.length,
c=[],d;a.iServerDraw++;c.push({name:"sEcho",value:a.iServerDraw});c.push({name:"iColumns",value:b});c.push({name:"sColumns",value:V(a)});c.push({name:"iDisplayStart",value:a._iDisplayStart});c.push({name:"iDisplayLength",value:a.oFeatures.bPaginate!==false?a._iDisplayLength:-1});if(a.oFeatures.bFilter!==false){c.push({name:"sSearch",value:a.oPreviousSearch.sSearch});c.push({name:"bEscapeRegex",value:a.oPreviousSearch.bEscapeRegex});for(d=0;d<b;d++){c.push({name:"sSearch_"+d,value:a.aoPreSearchCols[d].sSearch});
c.push({name:"bEscapeRegex_"+d,value:a.aoPreSearchCols[d].bEscapeRegex});c.push({name:"bSearchable_"+d,value:a.aoColumns[d].bSearchable})}}if(a.oFeatures.bSort!==false){var e=a.aaSortingFixed!==null?a.aaSortingFixed.length:0,f=a.aaSorting.length;c.push({name:"iSortingCols",value:e+f});for(d=0;d<e;d++){c.push({name:"iSortCol_"+d,value:a.aaSortingFixed[d][0]});c.push({name:"sSortDir_"+d,value:a.aaSortingFixed[d][1]})}for(d=0;d<f;d++){c.push({name:"iSortCol_"+(d+e),value:a.aaSorting[d][0]});c.push({name:"sSortDir_"+
(d+e),value:a.aaSorting[d][1]})}for(d=0;d<b;d++)c.push({name:"bSortable_"+d,value:a.aoColumns[d].bSortable})}a.fnServerData(a.sAjaxSource,c,function(h){a:{if(typeof h.sEcho!="undefined")if(h.sEcho*1<a.iServerDraw)break a;else a.iServerDraw=h.sEcho*1;W(a);a._iRecordsTotal=h.iTotalRecords;a._iRecordsDisplay=h.iTotalDisplayRecords;var i=V(a);if(i=typeof h.sColumns!="undefined"&&i!==""&&h.sColumns!=i)var j=ea(a,h.sColumns);for(var p=0,t=h.aaData.length;p<t;p++)if(i){for(var v=[],x=0,C=a.aoColumns.length;x<
C;x++)v.push(h.aaData[p][j[x]]);A(a,v)}else A(a,h.aaData[p]);a.aiDisplay=a.aiDisplayMaster.slice();a.bAjaxDataGet=false;r(a);a.bAjaxDataGet=true;D(a,false)}});return false}else return true}function ca(a){var b=document.createElement("div");a.nTable.parentNode.insertBefore(b,a.nTable);var c=document.createElement("div");c.className=a.oClasses.sWrapper;a.sTableId!==""&&c.setAttribute("id",a.sTableId+"_wrapper");var d=c,e=a.sDom.replace("H","fg-toolbar ui-widget-header ui-corner-tl ui-corner-tr ui-helper-clearfix");
e=e.replace("F","fg-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix");e=e.split("");for(var f,h,i,j,p,t,v,x=0;x<e.length;x++){h=0;i=e[x];if(i=="<"){j=document.createElement("div");p=e[x+1];if(p=="'"||p=='"'){t="";for(v=2;e[x+v]!=p;){t+=e[x+v];v++}j.className=t;x+=v}d.appendChild(j);d=j}else if(i==">")d=d.parentNode;else if(i=="l"&&a.oFeatures.bPaginate&&a.oFeatures.bLengthChange){f=fa(a);h=1}else if(i=="f"&&a.oFeatures.bFilter){f=ga(a);h=1}else if(i=="r"&&a.oFeatures.bProcessing){f=
ha(a);h=0}else if(i=="t"){f=a.nTable;h=1}else if(i=="i"&&a.oFeatures.bInfo){f=ia(a);h=1}else if(i=="p"&&a.oFeatures.bPaginate){f=ja(a);h=0}else if(n.aoFeatures.length!==0){j=n.aoFeatures;p=0;for(t=j.length;p<t;p++)if(i==j[p].cFeature){if(f=j[p].fnInit(a))h=1;break}}if(h==1){if(typeof a.aanFeatures[i]!="object")a.aanFeatures[i]=[];a.aanFeatures[i].push(f);d.appendChild(f)}}b.parentNode.replaceChild(c,b)}function ga(a){var b=document.createElement("div");a.sTableId!==""&&typeof a.aanFeatures.f=="undefined"&&
b.setAttribute("id",a.sTableId+"_filter");b.className=a.oClasses.sFilter;b.innerHTML=a.oLanguage.sSearch+(a.oLanguage.sSearch===""?"":" ")+'<input type="text" />';var c=m("input",b);c.val(a.oPreviousSearch.sSearch.replace('"',"&quot;"));c.keyup(function(){for(var d=a.aanFeatures.f,e=0,f=d.length;e<f;e++)d[e]!=this.parentNode&&m("input",d[e]).val(this.value);I(a,{sSearch:this.value,bEscapeRegex:a.oPreviousSearch.bEscapeRegex})});c.keypress(function(d){if(d.keyCode==13)return false});return b}function I(a,
b,c){ka(a,b.sSearch,c,b.bEscapeRegex);for(b=0;b<a.aoPreSearchCols.length;b++)la(a,a.aoPreSearchCols[b].sSearch,b,a.aoPreSearchCols[b].bEscapeRegex);if(n.afnFiltering.length!==0){b=n.afnFiltering;c=0;for(var d=b.length;c<d;c++)for(var e=0,f=0,h=a.aiDisplay.length;f<h;f++){var i=a.aiDisplay[f-e];if(!b[c](a,a.aoData[i]._aData,i)){a.aiDisplay.splice(f-e,1);e++}}}a.bFiltered=true;a._iDisplayStart=0;B(a);r(a);J(a,0)}function la(a,b,c,d){if(b!==""){var e=0;b=d?X(b):b;b=RegExp(b,"i");for(d=a.aiDisplay.length-
1;d>=0;d--){var f=Y(a.aoData[a.aiDisplay[d]]._aData[c],a.aoColumns[c].sType);if(!b.test(f)){a.aiDisplay.splice(d,1);e++}}}}function ka(a,b,c,d){if(typeof c=="undefined"||c===null)c=0;if(n.afnFiltering.length!==0)c=1;var e="^(?=.*?"+(d?X(b).split(" "):b.split(" ")).join(")(?=.*?")+").*$";e=RegExp(e,"i");if(b.length<=0){a.aiDisplay.splice(0,a.aiDisplay.length);a.aiDisplay=a.aiDisplayMaster.slice()}else if(a.aiDisplay.length==a.aiDisplayMaster.length||a.oPreviousSearch.sSearch.length>b.length||c==1||
b.indexOf(a.oPreviousSearch.sSearch)!==0){a.aiDisplay.splice(0,a.aiDisplay.length);J(a,1);for(c=0;c<a.aiDisplayMaster.length;c++)e.test(a.asDataSearch[c])&&a.aiDisplay.push(a.aiDisplayMaster[c])}else{var f=0;for(c=0;c<a.asDataSearch.length;c++)if(!e.test(a.asDataSearch[c])){a.aiDisplay.splice(c-f,1);f++}}a.oPreviousSearch.sSearch=b;a.oPreviousSearch.bEscapeRegex=d}function J(a,b){a.asDataSearch.splice(0,a.asDataSearch.length);for(var c=typeof b!="undefined"&&b==1?a.aiDisplayMaster:a.aiDisplay,d=0,
e=c.length;d<e;d++){a.asDataSearch[d]="";for(var f=0,h=a.aoColumns.length;f<h;f++)if(a.aoColumns[f].bSearchable)a.asDataSearch[d]+=Y(a.aoData[c[d]]._aData[f],a.aoColumns[f].sType)+" "}}function Y(a,b){if(typeof n.ofnSearch[b]=="function")return n.ofnSearch[b](a);else if(b=="html")return a.replace(/\n/g," ").replace(/<.*?>/g,"");else if(typeof a=="string")return a.replace(/\n/g," ");return a}function N(a,b){var c=[],d=n.oSort,e=a.aoData,f,h,i,j,p;if(!a.oFeatures.bServerSide&&(a.aaSorting.length!==
0||a.aaSortingFixed!==null)){c=a.aaSortingFixed!==null?a.aaSortingFixed.concat(a.aaSorting):a.aaSorting.slice();for(i=0;i<c.length;i++){f=c[i][0];j=a.aoColumns[f].sSortDataType;if(typeof n.afnSortData[j]!="undefined"){h=0;var t=n.afnSortData[j](a,f);j=0;for(p=e.length;j<p;j++)if(e[j]!==null){e[j]._aData[f]=t[h];h++}}}if(window.runtime){var v=[],x=c.length;for(i=0;i<x;i++){f=a.aoColumns[c[i][0]].iDataSort;v.push([f,a.aoColumns[f].sType+"-"+c[i][1]])}a.aiDisplayMaster.sort(function(C,K){for(var E,G=
0;G<x;G++){E=d[v[G][1]](e[C]._aData[v[G][0]],e[K]._aData[v[G][0]]);if(E!==0)return E}return 0})}else{j="fnLocalSorting = function(a,b){var iTest;";for(i=0;i<c.length-1;i++){f=a.aoColumns[c[i][0]].iDataSort;h=a.aoColumns[f].sType;j+="iTest = oSort['"+h+"-"+c[i][1]+"']( aoData[a]._aData["+f+"], aoData[b]._aData["+f+"] ); if ( iTest === 0 )"}f=a.aoColumns[c[c.length-1][0]].iDataSort;h=a.aoColumns[f].sType;j+="iTest = oSort['"+h+"-"+c[c.length-1][1]+"']( aoData[a]._aData["+f+"], aoData[b]._aData["+f+
"] );if (iTest===0) return oSort['numeric-"+c[c.length-1][1]+"'](a, b); return iTest;}";eval(j);a.aiDisplayMaster.sort(void 0)}}if(typeof b=="undefined"||b)O(a);a.bSorted=true;if(a.oFeatures.bFilter)I(a,a.oPreviousSearch,1);else{a.aiDisplay=a.aiDisplayMaster.slice();a._iDisplayStart=0;B(a);r(a)}}function da(a,b,c,d){m(b).click(function(e){if(a.aoColumns[c].bSortable!==false){var f=function(){var h,i;if(e.shiftKey){for(var j=false,p=0;p<a.aaSorting.length;p++)if(a.aaSorting[p][0]==c){j=true;h=a.aaSorting[p][0];
i=a.aaSorting[p][2]+1;if(typeof a.aoColumns[h].asSorting[i]=="undefined")a.aaSorting.splice(p,1);else{a.aaSorting[p][1]=a.aoColumns[h].asSorting[i];a.aaSorting[p][2]=i}break}j===false&&a.aaSorting.push([c,a.aoColumns[c].asSorting[0],0])}else if(a.aaSorting.length==1&&a.aaSorting[0][0]==c){h=a.aaSorting[0][0];i=a.aaSorting[0][2]+1;if(typeof a.aoColumns[h].asSorting[i]=="undefined")i=0;a.aaSorting[0][1]=a.aoColumns[h].asSorting[i];a.aaSorting[0][2]=i}else{a.aaSorting.splice(0,a.aaSorting.length);a.aaSorting.push([c,
a.aoColumns[c].asSorting[0],0])}N(a)};if(a.oFeatures.bProcessing){D(a,true);setTimeout(function(){f();a.oFeatures.bServerSide||D(a,false)},0)}else f();typeof d=="function"&&d(a)}})}function O(a){var b,c,d,e,f,h=a.aoColumns.length,i=a.oClasses;for(b=0;b<h;b++)a.aoColumns[b].bSortable&&m(a.aoColumns[b].nTh).removeClass(i.sSortAsc+" "+i.sSortDesc+" "+a.aoColumns[b].sSortingClass);e=a.aaSortingFixed!==null?a.aaSortingFixed.concat(a.aaSorting):a.aaSorting.slice();for(b=0;b<a.aoColumns.length;b++)if(a.aoColumns[b].bSortable){f=
a.aoColumns[b].sSortingClass;d=-1;for(c=0;c<e.length;c++)if(e[c][0]==b){f=e[c][1]=="asc"?i.sSortAsc:i.sSortDesc;d=c;break}m(a.aoColumns[b].nTh).addClass(f);if(a.bJUI){c=m("span",a.aoColumns[b].nTh);c.removeClass(i.sSortJUIAsc+" "+i.sSortJUIDesc+" "+i.sSortJUI+" "+i.sSortJUIAscAllowed+" "+i.sSortJUIDescAllowed);c.addClass(d==-1?a.aoColumns[b].sSortingClassJUI:e[d][1]=="asc"?i.sSortJUIAsc:i.sSortJUIDesc)}}else m(a.aoColumns[b].nTh).addClass(a.aoColumns[b].sSortingClass);f=i.sSortColumn;if(a.oFeatures.bSort&&
a.oFeatures.bSortClasses){d=R(a);if(d.length>=h)for(b=0;b<h;b++)if(d[b].className.indexOf(f+"1")!=-1){c=0;for(a=d.length/h;c<a;c++)d[h*c+b].className=d[h*c+b].className.replace(" "+f+"1","")}else if(d[b].className.indexOf(f+"2")!=-1){c=0;for(a=d.length/h;c<a;c++)d[h*c+b].className=d[h*c+b].className.replace(" "+f+"2","")}else if(d[b].className.indexOf(f+"3")!=-1){c=0;for(a=d.length/h;c<a;c++)d[h*c+b].className=d[h*c+b].className.replace(" "+f+"3","")}i=1;var j;for(b=0;b<e.length;b++){j=parseInt(e[b][0],
10);c=0;for(a=d.length/h;c<a;c++)d[h*c+j].className+=" "+f+i;i<3&&i++}}}function ja(a){var b=document.getElementById("filter_pagination");b.className=a.oClasses.sPaging+a.sPaginationType;n.oPagination[a.sPaginationType].fnInit(a,b,function(c){B(c);r(c)});typeof a.aanFeatures.p=="undefined"&&a.aoDrawCallback.push({fn:function(c){n.oPagination[c.sPaginationType].fnUpdate(c,function(d){B(d);r(d)})},sName:"pagination"});return b}function ma(a,b){var c=a._iDisplayStart;if(b=="first")a._iDisplayStart=0;
else if(b=="previous"){a._iDisplayStart=a._iDisplayLength>=0?a._iDisplayStart-a._iDisplayLength:0;if(a._iDisplayStart<0)a._iDisplayStart=0}else if(b=="next")if(a._iDisplayLength>=0){if(a._iDisplayStart+a._iDisplayLength<a.fnRecordsDisplay())a._iDisplayStart+=a._iDisplayLength}else a._iDisplayStart=0;else if(b=="last")if(a._iDisplayLength>=0){var d=parseInt((a.fnRecordsDisplay()-1)/a._iDisplayLength,10)+1;a._iDisplayStart=(d-1)*a._iDisplayLength}else a._iDisplayStart=0;else alert("DataTables warning: unknown paging action: "+
b);return c!=a._iDisplayStart}function ia(a){var b=document.createElement("div");b.className=a.oClasses.sInfo;if(typeof a.aanFeatures.i=="undefined"){a.aoDrawCallback.push({fn:ua,sName:"information"});a.sTableId!==""&&b.setAttribute("id",a.sTableId+"_info")}return b}function ua(a){if(!(!a.oFeatures.bInfo||a.aanFeatures.i.length===0)){var b=a.aanFeatures.i[0],c=1;if(a.fnDisplayEnd()==0)c=0;b.innerHTML=a.fnRecordsDisplay()===0&&a.fnRecordsDisplay()==a.fnRecordsTotal()?a.oLanguage.sInfoEmpty+a.oLanguage.sInfoPostFix:
a.fnRecordsDisplay()===0?a.oLanguage.sInfoEmpty+" "+a.oLanguage.sInfoFiltered.replace("_MAX_",a.fnRecordsTotal())+a.oLanguage.sInfoPostFix:a.fnRecordsDisplay()==a.fnRecordsTotal()?a.oLanguage.sInfo.replace("_START_",a._iDisplayStart+c).replace("_END_",a.fnDisplayEnd()).replace("_TOTAL_",a.fnRecordsDisplay())+a.oLanguage.sInfoPostFix:a.oLanguage.sInfo.replace("_START_",a._iDisplayStart+c).replace("_END_",a.fnDisplayEnd()).replace("_TOTAL_",a.fnRecordsDisplay())+" "+a.oLanguage.sInfoFiltered.replace("_MAX_",
a.fnRecordsTotal())+a.oLanguage.sInfoPostFix;a=a.aanFeatures.i;if(a.length>1){b=b.innerHTML;c=1;for(var d=a.length;c<d;c++)a[c].innerHTML=b}}}function fa(a){var b='<select size="1" '+(a.sTableId===""?"":'name="'+a.sTableId+'_length"')+'><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>',c=document.createElement("div");a.sTableId!==""&&typeof a.aanFeatures.l=="undefined"&&c.setAttribute("id",a.sTableId+"_length");c.className=
a.oClasses.sLength;c.innerHTML=a.oLanguage.sLengthMenu.replace("_MENU_",b);m('select option[value="'+a._iDisplayLength+'"]',c).attr("selected",true);m("select",c).change(function(){for(var d=m(this).val(),e=a.aanFeatures.l,f=0,h=e.length;f<h;f++)e[f]!=this.parentNode&&m("select",e[f]).val(d);a._iDisplayLength=parseInt(d,10);B(a);if(a._iDisplayEnd==a.aiDisplay.length){a._iDisplayStart=a._iDisplayEnd-a._iDisplayLength;if(a._iDisplayStart<0)a._iDisplayStart=0}if(a._iDisplayLength==-1)a._iDisplayStart=
0;r(a)});return c}function ha(){return document.getElementById("filter_ajax_indicator")}function D(a,b){if(a.oFeatures.bProcessing)for(var c=document.getElementById("filter_ajax_indicator"),d=0,e=c.length;d<e;d++)c[d].style.visibility=b?"visible":"hidden"}function S(a,b){for(var c=-1,d=0;d<a.aoColumns.length;d++){a.aoColumns[d].bVisible===true&&c++;if(d==b)return a.aoColumns[d].bVisible===true?c:null}return null}function L(a,b){for(var c=0,d=a.aoData.length;c<d;c++)if(a.aoData[c]!==null&&a.aoData[c].nTr==
b)return c;return null}function T(a){for(var b=0,c=0;c<a.aoColumns.length;c++)a.aoColumns[c].bVisible===true&&b++;return b}function B(a){a._iDisplayEnd=a.oFeatures.bPaginate===false?a.aiDisplay.length:a._iDisplayStart+a._iDisplayLength>a.aiDisplay.length||a._iDisplayLength==-1?a.aiDisplay.length:a._iDisplayStart+a._iDisplayLength}function na(a,b){if(!a||a===null||a==="")return 0;if(typeof b=="undefined")b=document.getElementsByTagName("body")[0];var c,d=document.createElement("div");d.style.width=
a;b.appendChild(d);c=d.offsetWidth;b.removeChild(d);return c}function oa(a){var b=a.nTable.offsetWidth,c=0,d,e=0,f=a.aoColumns.length,h,i=m("thead:eq(0)>th",a.nTable);for(h=0;h<f;h++)if(a.aoColumns[h].bVisible){e++;if(a.aoColumns[h].sWidth!==null){d=na(a.aoColumns[h].sWidth,a.nTable.parentNode);c+=d;a.aoColumns[h].sWidth=d+"px"}}if(f==i.length&&c===0&&e==f)for(h=0;h<a.aoColumns.length;h++)a.aoColumns[h].sWidth=i[h].offsetWidth+"px";else{c=a.nTable.cloneNode(false);c.setAttribute("id","");c='<table class="'+
c.className+'">';e=d="<tr>";for(h=0;h<f;h++)if(a.aoColumns[h].bVisible){d+="<th>"+a.aoColumns[h].sTitle+"</th>";if(a.aoColumns[h].sWidth!==null){i="";if(a.aoColumns[h].sWidth!==null)i=' style="width:'+a.aoColumns[h].sWidth+';"';e+="<td"+i+' tag_index="'+h+'">'+pa(a,h)+"</td>"}else e+='<td tag_index="'+h+'">'+pa(a,h)+"</td>"}d+="</tr>";e+="</tr>";c=m(c+d+e+"</table>")[0];c.style.width=b+"px";c.style.visibility="hidden";c.style.position="absolute";a.nTable.parentNode.appendChild(c);b=m("tr:eq(1)>td",
c);for(h=0;h<b.length;h++){f=b[h].getAttribute("tag_index");d=m("td",c).eq(h).width();e=a.aoColumns[h].sWidth?a.aoColumns[h].sWidth.slice(0,-2):0;a.aoColumns[f].sWidth=Math.max(d,e)+"px"}a.nTable.parentNode.removeChild(c)}}function pa(a,b){for(var c=0,d=-1,e=0;e<a.aoData.length;e++)if(a.aoData[e]._aData[b].length>c){c=a.aoData[e]._aData[b].length;d=e}if(d>=0)return a.aoData[d]._aData[b];return""}function U(a){for(var b=n.aTypes,c=b.length,d=0;d<c;d++){var e=b[d](a);if(e!==null)return e}return"string"}
function z(a){for(var b=0;b<F.length;b++)if(F[b].nTable==a)return F[b];return null}function Q(a){for(var b=[],c=a.aoData.length,d=0;d<c;d++)a.aoData[d]===null?b.push(null):b.push(a.aoData[d]._aData);return b}function P(a){for(var b=[],c=a.aoData.length,d=0;d<c;d++)a.aoData[d]===null?b.push(null):b.push(a.aoData[d].nTr);return b}function R(a){var b=P(a),c=[],d,e=[],f,h,i,j;f=0;for(h=b.length;f<h;f++){c=[];i=0;for(j=b[f].childNodes.length;i<j;i++){d=b[f].childNodes[i];d.nodeName=="TD"&&c.push(d)}i=
d=0;for(j=a.aoColumns.length;i<j;i++)if(a.aoColumns[i].bVisible)e.push(c[i-d]);else{e.push(a.aoData[f]._anHidden[i]);d++}}return e}function X(a){var b=RegExp("(\\/|\\.|\\*|\\+|\\?|\\||\\(|\\)|\\[|\\]|\\{|\\}|\\\\|\\$|\\^)","g");return a.replace(b,"\\$1")}function ea(a,b){for(var c=b.split(","),d=[],e=0,f=a.aoColumns.length;e<f;e++)for(var h=0;h<f;h++)if(a.aoColumns[e].sName==c[h]){d.push(h);break}return d}function V(a){for(var b="",c=0,d=a.aoColumns.length;c<d;c++)b+=a.aoColumns[c].sName+",";if(b.length==
d)return"";return b.slice(0,-1)}function W(a){a.aoData.length=0;a.aiDisplayMaster.length=0;a.aiDisplay.length=0;B(a)}function Z(a){if(a.oFeatures.bStateSave){var b,c="{";c+='"iStart": '+a._iDisplayStart+",";c+='"iEnd": '+a._iDisplayEnd+",";c+='"iLength": '+a._iDisplayLength+",";c+='"sFilter": "'+a.oPreviousSearch.sSearch.replace('"','\\"')+'",';c+='"sFilterEsc": '+a.oPreviousSearch.bEscapeRegex+",";c+='"aaSorting": [ ';for(b=0;b<a.aaSorting.length;b++)c+="["+a.aaSorting[b][0]+",'"+a.aaSorting[b][1]+
"'],";c=c.substring(0,c.length-1);c+="],";c+='"aaSearchCols": [ ';for(b=0;b<a.aoPreSearchCols.length;b++)c+="['"+a.aoPreSearchCols[b].sSearch.replace("'","'")+"',"+a.aoPreSearchCols[b].bEscapeRegex+"],";c=c.substring(0,c.length-1);c+="],";c+='"abVisCols": [ ';for(b=0;b<a.aoColumns.length;b++)c+=a.aoColumns[b].bVisible+",";c=c.substring(0,c.length-1);c+="]";c+="}";qa("SpryMedia_DataTables_"+a.sInstance,c,a.iCookieDuration)}}function ra(a,b){if(a.oFeatures.bStateSave){var c,d=sa("SpryMedia_DataTables_"+
a.sInstance);if(d!==null&&d!==""){try{c=typeof JSON=="object"&&typeof JSON.parse=="function"?JSON.parse(d.replace(/'/g,'"')):eval("("+d+")")}catch(e){return}a._iDisplayStart=c.iStart;a.iInitDisplayStart=c.iStart;a._iDisplayEnd=c.iEnd;a._iDisplayLength=c.iLength;a.oPreviousSearch.sSearch=c.sFilter;a.aaSorting=c.aaSorting.slice();a.saved_aaSorting=c.aaSorting.slice();if(typeof c.sFilterEsc!="undefined")a.oPreviousSearch.bEscapeRegex=c.sFilterEsc;if(typeof c.aaSearchCols!="undefined")for(d=0;d<c.aaSearchCols.length;d++)a.aoPreSearchCols[d]=
{sSearch:c.aaSearchCols[d][0],bEscapeRegex:c.aaSearchCols[d][1]};if(typeof c.abVisCols!="undefined"){b.saved_aoColumns=[];for(d=0;d<c.abVisCols.length;d++){b.saved_aoColumns[d]={};b.saved_aoColumns[d].bVisible=c.abVisCols[d]}}}}}function qa(a,b,c){var d=new Date;d.setTime(d.getTime()+c*1E3);a+="_"+window.location.pathname.replace(/[\/:]/g,"").toLowerCase();document.cookie=a+"="+encodeURIComponent(b)+"; expires="+d.toGMTString()+"; path=/"}function sa(a){a=a+"_"+window.location.pathname.replace(/[\/:]/g,
"").toLowerCase()+"=";for(var b=document.cookie.split(";"),c=0;c<b.length;c++){for(var d=b[c];d.charAt(0)==" ";)d=d.substring(1,d.length);if(d.indexOf(a)===0)return decodeURIComponent(d.substring(a.length,d.length))}return null}function ta(a){a=a.getElementsByTagName("tr");if(a.length==1)return a[0].getElementsByTagName("th");var b=[],c=[],d,e,f,h,i,j,p=function(E,G,aa){for(;typeof E[G][aa]!="undefined";)aa++;return aa},t=function(E){if(typeof b[E]=="undefined")b[E]=[]};d=0;for(h=a.length;d<h;d++){t(d);
var v=0,x=[];e=0;for(i=a[d].childNodes.length;e<i;e++)if(a[d].childNodes[e].nodeName=="TD"||a[d].childNodes[e].nodeName=="TH")x.push(a[d].childNodes[e]);e=0;for(i=x.length;e<i;e++){var C=x[e].getAttribute("colspan")*1,K=x[e].getAttribute("rowspan")*1;if(!C||C===0||C===1){j=p(b,d,v);b[d][j]=x[e].nodeName=="TD"?4:x[e];if(K||K===0||K===1)for(f=1;f<K;f++){t(d+f);b[d+f][j]=2}v++}else{j=p(b,d,v);for(f=0;f<C;f++)b[d][j+f]=3;v+=C}}}d=0;for(h=b[0].length;d<h;d++){e=0;for(i=b.length;e<i;e++)typeof b[e][d]==
"object"&&c.push(b[e][d])}return c}function o(a,b,c,d){if(typeof d=="undefined")d=c;if(typeof b[c]!="undefined")a[d]=b[c]}this.oApi={};this.fnDraw=function(a){var b=z(this[n.iApiIndex]);if(typeof a!="undefined"&&a===false){B(b);r(b)}else H(b)};this.fnFilter=function(a,b,c){var d=z(this[n.iApiIndex]);if(typeof c=="undefined")c=true;if(typeof b=="undefined"||b===null)I(d,{sSearch:a,bEscapeRegex:c},1);else{d.aoPreSearchCols[b].sSearch=a;d.aoPreSearchCols[b].bEscapeRegex=c;I(d,d.oPreviousSearch,1)}};
this.fnSettings=function(){return z(this[n.iApiIndex])};this.fnVersionCheck=function(a){var b=function(i,j){for(;i.length<j;)i+="0";return i},c=n.sVersion.split(".");a=a.split(".");for(var d="",e="",f=0,h=a.length;f<h;f++){d+=b(c[f],3);e+=b(a[f],3)}return parseInt(d,10)>=parseInt(e,10)};this.fnSort=function(a){var b=z(this[n.iApiIndex]);b.aaSorting=a;N(b)};this.fnSortListener=function(a,b,c){da(z(this[n.iApiIndex]),a,b,c)};this.fnAddData=function(a,b){if(a.length===0)return[];var c=[],d,e=z(this[n.iApiIndex]);
if(typeof a[0]=="object")for(var f=0;f<a.length;f++){d=A(e,a[f]);if(d==-1)return c;c.push(d)}else{d=A(e,a);if(d==-1)return c;c.push(d)}e.aiDisplay=e.aiDisplayMaster.slice();J(e,1);if(typeof b=="undefined"||b)H(e);return c};this.fnDeleteRow=function(a,b,c){var d=z(this[n.iApiIndex]),e;a=typeof a=="object"?L(d,a):a;for(e=0;e<d.aiDisplayMaster.length;e++)if(d.aiDisplayMaster[e]==a){d.aiDisplayMaster.splice(e,1);break}for(e=0;e<d.aiDisplay.length;e++)if(d.aiDisplay[e]==a){d.aiDisplay.splice(e,1);break}J(d,
1);typeof b=="function"&&b.call(this);if(d._iDisplayStart>=d.aiDisplay.length){d._iDisplayStart-=d._iDisplayLength;if(d._iDisplayStart<0)d._iDisplayStart=0}B(d);r(d);b=d.aoData[a]._aData.slice();if(typeof c!="undefined"&&c===true)d.aoData[a]=null;return b};this.fnClearTable=function(a){var b=z(this[n.iApiIndex]);W(b);if(typeof a=="undefined"||a)r(b)};this.fnOpen=function(a,b,c){var d=z(this[n.iApiIndex]);this.fnClose(a);var e=document.createElement("tr"),f=document.createElement("td");e.appendChild(f);
f.className=c;f.colSpan=T(d);f.innerHTML=b;b=m("tbody tr",d.nTable);m.inArray(a,b)!=-1&&m(e).insertAfter(a);d.oFeatures.bServerSide||d.aoOpenRows.push({nTr:e,nParent:a});return e};this.fnClose=function(a){for(var b=z(this[n.iApiIndex]),c=0;c<b.aoOpenRows.length;c++)if(b.aoOpenRows[c].nParent==a){(a=b.aoOpenRows[c].nTr.parentNode)&&a.removeChild(b.aoOpenRows[c].nTr);b.aoOpenRows.splice(c,1);return 0}return 1};this.fnGetData=function(a){var b=z(this[n.iApiIndex]);if(typeof a!="undefined"){a=typeof a==
"object"?L(b,a):a;return b.aoData[a]._aData}return Q(b)};this.fnGetNodes=function(a){var b=z(this[n.iApiIndex]);if(typeof a!="undefined")return b.aoData[a].nTr;return P(b)};this.fnGetPosition=function(a){var b=z(this[n.iApiIndex]);if(a.nodeName=="TR")return L(b,a);else if(a.nodeName=="TD")for(var c=L(b,a.parentNode),d=0,e=0;e<b.aoColumns.length;e++)if(b.aoColumns[e].bVisible){if(b.aoData[c].nTr.getElementsByTagName("td")[e-d]==a)return[c,e-d,e]}else d++;return null};this.fnUpdate=function(a,b,c,d){var e=
z(this[n.iApiIndex]),f=typeof b=="object"?L(e,b):b;if(typeof a!="object"){b=a;e.aoData[f]._aData[c]=b;if(e.aoColumns[c].fnRender!==null){b=e.aoColumns[c].fnRender({iDataRow:f,iDataColumn:c,aData:e.aoData[f]._aData,oSettings:e});if(e.aoColumns[c].bUseRendered)e.aoData[f]._aData[c]=b}c=S(e,c);if(c!==null)e.aoData[f].nTr.getElementsByTagName("td")[c].innerHTML=b}else{if(a.length!=e.aoColumns.length){alert("DataTables warning: An array passed to fnUpdate must have the same number of columns as the table in question - in this case "+
e.aoColumns.length);return 1}for(var h=0;h<a.length;h++){b=a[h];e.aoData[f]._aData[h]=b;if(e.aoColumns[h].fnRender!==null){b=e.aoColumns[h].fnRender({iDataRow:f,iDataColumn:h,aData:e.aoData[f]._aData,oSettings:e});if(e.aoColumns[h].bUseRendered)e.aoData[f]._aData[h]=b}c=S(e,h);if(c!==null)e.aoData[f].nTr.getElementsByTagName("td")[c].innerHTML=b}}J(e,1);typeof d!="undefined"&&d&&H(e);return 0};this.fnSetColumnVis=function(a,b){var c=z(this[n.iApiIndex]),d,e;e=c.aoColumns.length;var f,h;if(c.aoColumns[a].bVisible!=
b){f=m("thead:eq(0)>tr",c.nTable)[0];var i=m("tfoot:eq(0)>tr",c.nTable)[0],j=[],p=[];for(d=0;d<e;d++){j.push(c.aoColumns[d].nTh);p.push(c.aoColumns[d].nTf)}if(b){var t=0;for(d=0;d<a;d++)c.aoColumns[d].bVisible&&t++;if(t>=T(c)){f.appendChild(j[a]);i&&i.appendChild(p[a]);d=0;for(e=c.aoData.length;d<e;d++){f=c.aoData[d]._anHidden[a];c.aoData[d].nTr.appendChild(f)}}else{for(d=a;d<e;d++){h=S(c,d);if(h!==null)break}f.insertBefore(j[a],f.getElementsByTagName("th")[h]);i&&i.insertBefore(p[a],i.getElementsByTagName("th")[h]);
R(c);d=0;for(e=c.aoData.length;d<e;d++){f=c.aoData[d]._anHidden[a];c.aoData[d].nTr.insertBefore(f,m(">td:eq("+h+")",c.aoData[d].nTr)[0])}}c.aoColumns[a].bVisible=true}else{f.removeChild(j[a]);i&&i.removeChild(p[a]);h=R(c);d=0;for(e=c.aoData.length;d<e;d++){f=h[d*c.aoColumns.length+a];c.aoData[d]._anHidden[a]=f;f.parentNode.removeChild(f)}c.aoColumns[a].bVisible=false}d=0;for(e=c.aoOpenRows.length;d<e;d++)c.aoOpenRows[d].nTr.colSpan=T(c);Z(c)}};this.fnPageChange=function(a,b){var c=z(this[n.iApiIndex]);
ma(c,a);B(c);if(typeof b=="undefined"||b)r(c)};for(var ba in n.oApi)if(ba)this[ba]=k(ba);this.oApi._fnInitalise=l;this.oApi._fnLanguageProcess=s;this.oApi._fnAddColumn=w;this.oApi._fnAddData=A;this.oApi._fnGatherData=y;this.oApi._fnDrawHead=u;this.oApi._fnDraw=r;this.oApi._fnAjaxUpdate=M;this.oApi._fnAddOptionsHtml=ca;this.oApi._fnFeatureHtmlFilter=ga;this.oApi._fnFeatureHtmlInfo=ia;this.oApi._fnFeatureHtmlPaginate=ja;this.oApi._fnPageChange=ma;this.oApi._fnFeatureHtmlLength=fa;this.oApi._fnFeatureHtmlProcessing=
ha;this.oApi._fnProcessingDisplay=D;this.oApi._fnFilterComplete=I;this.oApi._fnFilterColumn=la;this.oApi._fnFilter=ka;this.oApi._fnSortingClasses=O;this.oApi._fnVisibleToColumnIndex=function(a,b){for(var c=-1,d=0;d<a.aoColumns.length;d++){a.aoColumns[d].bVisible===true&&c++;if(c==b)return d}return null};this.oApi._fnColumnIndexToVisible=S;this.oApi._fnNodeToDataIndex=L;this.oApi._fnVisbleColumns=T;this.oApi._fnBuildSearchArray=J;this.oApi._fnDataToSearch=Y;this.oApi._fnCalculateEnd=B;this.oApi._fnConvertToWidth=
na;this.oApi._fnCalculateColumnWidths=oa;this.oApi._fnArrayCmp=function(a,b){if(a.length!=b.length)return 1;for(var c=0;c<a.length;c++)if(a[c]!=b[c])return 2;return 0};this.oApi._fnDetectType=U;this.oApi._fnGetDataMaster=Q;this.oApi._fnGetTrNodes=P;this.oApi._fnGetTdNodes=R;this.oApi._fnEscapeRegex=X;this.oApi._fnReOrderIndex=ea;this.oApi._fnColumnOrdering=V;this.oApi._fnClearTable=W;this.oApi._fnSaveState=Z;this.oApi._fnLoadState=ra;this.oApi._fnCreateCookie=qa;this.oApi._fnReadCookie=sa;this.oApi._fnGetUniqueThs=
ta;this.oApi._fnReDraw=H;var va=this;return this.each(function(){var a=0,b,c,d;a=0;for(b=F.length;a<b;a++)if(F[a].nTable==this){alert("DataTables warning: Unable to re-initialise DataTable. Please use the API to make any configuration changes required.");return F[a]}var e=new q;F.push(e);var f=false,h=false;a=this.getAttribute("id");if(a!==null){e.sTableId=a;e.sInstance=a}else e.sInstance=n._oExternConfig.iNextUnique++;e.nTable=this;e.oApi=va.oApi;if(typeof g!="undefined"&&g!==null){o(e.oFeatures,
g,"bPaginate");o(e.oFeatures,g,"bLengthChange");o(e.oFeatures,g,"bFilter");o(e.oFeatures,g,"bSort");o(e.oFeatures,g,"bInfo");o(e.oFeatures,g,"bProcessing");o(e.oFeatures,g,"bAutoWidth");o(e.oFeatures,g,"bSortClasses");o(e.oFeatures,g,"bServerSide");o(e,g,"asStripClasses");o(e,g,"fnRowCallback");o(e,g,"fnHeaderCallback");o(e,g,"fnFooterCallback");o(e,g,"fnInitComplete");o(e,g,"fnServerData");o(e,g,"aaSorting");o(e,g,"aaSortingFixed");o(e,g,"sPaginationType");o(e,g,"sAjaxSource");o(e,g,"iCookieDuration");
o(e,g,"sDom");o(e,g,"oSearch","oPreviousSearch");o(e,g,"aoSearchCols","aoPreSearchCols");o(e,g,"iDisplayLength","_iDisplayLength");o(e,g,"bJQueryUI","bJUI");typeof g.fnDrawCallback=="function"&&e.aoDrawCallback.push({fn:g.fnDrawCallback,sName:"user"});e.oFeatures.bServerSide&&e.oFeatures.bSort&&e.oFeatures.bSortClasses&&e.aoDrawCallback.push({fn:O,sName:"server_side_sort_classes"});if(typeof g.bJQueryUI!="undefined"&&g.bJQueryUI){e.oClasses=n.oJUIClasses;if(typeof g.sDom=="undefined")e.sDom='<"H"lfr>t<"F"ip>'}if(typeof g.iDisplayStart!=
"undefined"&&typeof e.iInitDisplayStart=="undefined"){e.iInitDisplayStart=g.iDisplayStart;e._iDisplayStart=g.iDisplayStart}if(typeof g.bStateSave!="undefined"){e.oFeatures.bStateSave=g.bStateSave;ra(e,g);e.aoDrawCallback.push({fn:Z,sName:"state_save"})}if(typeof g.aaData!="undefined")h=true;if(typeof g!="undefined"&&typeof g.aoData!="undefined")g.aoColumns=g.aoData;if(typeof g.oLanguage!="undefined")if(typeof g.oLanguage.sUrl!="undefined"&&g.oLanguage.sUrl!==""){e.oLanguage.sUrl=g.oLanguage.sUrl;
m.getJSON(e.oLanguage.sUrl,null,function(p){s(e,p,true)});f=true}else s(e,g.oLanguage,false)}else g={};if(typeof g.asStripClasses=="undefined"){e.asStripClasses.push(e.oClasses.sStripOdd);e.asStripClasses.push(e.oClasses.sStripEven)}a=this.getElementsByTagName("thead");c=a.length===0?null:ta(a[0]);d=typeof g.aoColumns!="undefined";a=0;for(b=d?g.aoColumns.length:c.length;a<b;a++){var i=d?g.aoColumns[a]:null,j=c?c[a]:null;if(typeof g.saved_aoColumns!="undefined"&&g.saved_aoColumns.length==b){if(i===
null)i={};i.bVisible=g.saved_aoColumns[a].bVisible}w(e,i,j)}a=0;for(b=e.aaSorting.length;a<b;a++){i=e.aoColumns[e.aaSorting[a][0]];if(typeof e.aaSorting[a][2]=="undefined")e.aaSorting[a][2]=0;if(typeof g.aaSorting=="undefined"&&typeof e.saved_aaSorting=="undefined")e.aaSorting[a][1]=i.asSorting[0];c=0;for(d=i.asSorting.length;c<d;c++)if(e.aaSorting[a][1]==i.asSorting[c]){e.aaSorting[a][2]=c;break}}this.getElementsByTagName("thead").length===0&&this.appendChild(document.createElement("thead"));this.getElementsByTagName("tbody").length===
0&&this.appendChild(document.createElement("tbody"));if(h)for(a=0;a<g.aaData.length;a++)A(e,g.aaData[a]);else y(e);e.aiDisplay=e.aiDisplayMaster.slice();e.oFeatures.bAutoWidth&&oa(e);e.bInitialised=true;f===false&&l(e)})}})(jQuery);$.extend(true,$.fn.dataTableExt,{oStdClasses:{sSortAsc:"headerSortUp",sSortDesc:"headerSortDown"},oPagination:{iFullNumbersShowPages:3}});
