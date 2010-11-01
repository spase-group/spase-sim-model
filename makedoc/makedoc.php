#!/user/bin/php
<?php
// Designed for the SPASE website envronment.
// Written by: Todd King (June 2005)
// Copyright 2005 Regents University of California. All Rights Reserved
define('FPDF_FONTPATH','/var/www/fpdf/font/');
require('fpdf.php');
// Load passed arguments - stored in the array $Parameter
// for ($i=1; $i < $argc; $i++) {include($argv[$i]);}

// Database access variables
$Host = "127.0.0.1";
$Database = "spase";
$Username = "spase-user";
$Password = "my123";

$TOC = array();
$IndexTemp = array();
$Index = array();

$Doclet = "/var/www/spase/root/data/doclet/";

$DatabaseConn = NULL;

$ReleaseDate = date('Y-M-d');

$GroupIndex = array();

$SectionIndex = array();
$SectionIndex[1] = 0;
$SectionIndex[2] = 0;
$SectionIndex[3] = 0;
$SectionIndex[4] = 0;

$Document = array();
$Document['Font'] = 'Times';
$Document['FontSize'] = 12;
$Document['FontPre'] = 'Courier';
$Document['FontSizePre'] = 10;
$Document['FontSizeDef'] = 12;
$Document['Section1Size'] = 14;
$Document['Section2Size'] = 12;
$Document['Section3Size'] = 12;

$Document['LeftMargin'] = 20;
$Document['Indent'] = 10;
$Document['LineSpace'] = 5;
$Document['LineSpaceDef'] = 4;
$Document['LineSpacePre'] = 4;

$Document['Title'] = 'SPASE Data Model';
$Document['Author'] = 'SPASE Consortium';

$Document['PageOffset'] = 0;
$Document['NumberRoman'] = 0;

$Document['NumberChange'] = 0;
$Document['ShowTrim'] = 1;
$Document['ListMode'] = 0;
$Document['PartialPage'] = 0;

$Document['Columns'] = 1;

class PDF extends FPDF
{
var $Column = 1;
var $PageTop;

function Header()
{
    global $Document;
    
	 if(! $Document['ShowTrim']) return;
	
    $this->SetFont($Document['Font'], '', 10);
    $this->SetTextColor(128);	// Grey
    $this->Cell(0, 3, $Document['Title']);
    $this->Ln(10);
    $this->PageTop = $this->GetY();
}

function Footer()
{
	global $Document;
	
	if(! $Document['ShowTrim']) {
		$Document['ShowTrim'] = 1;
		return;
	}
	
    //Position at 1.5 cm from bottom
   $this->SetY(-15);
   $this->SetFont($Document['Font'], '', 10);
   $this->SetTextColor(0);	// Black
   $number = $this->PageNo();
   if($this->PageNo() > $Document['PageOffset']) $number -= $Document['PageOffset'];
   
   if($Document['NumberRoman']) $buffer = $this->ArabicToRoman($number - $Document['PartialPage']);
   else $buffer = $number;
   $label = '-' . $buffer . '-';
   $this->Cell(0, 10, $label, 0, 0, 'C');     //Page number
   // Change page number syle after footer is written
   if($Document['NumberChange']) $Document['NumberRoman'] = 0;
}

function SetCol($col)
{
   global $Document;
   
   //Set position at a given column
   $this->Column = $col;
   $colWidth = 180 / $Document['Columns'];
   $x = $Document['LeftMargin'] + (($col - 1) * $colWidth);
   $this->SetLeftMargin($x);
   $this->SetX($x);
}

function AcceptPageBreak()
{
   global $Document;
   
   //Method accepting or not automatic page break
   if($Document['Columns'] == 1) return true;
   
   if($this->Column < $Document['Columns']) {
        $this->SetCol($this->Column + 1);   // Next Column
        $this->SetY($this->PageTop);  //Set ordinate to top
        return false;            //Keep on page
    } else {
        $this->SetCol(1);  // Set to first column
        return true;       // Page break;
    }
}
function Section($label, $level)
{
	global $SectionIndex;
	global $Document;
	
	$section = "";
	$fontSize = $Document['FontSize'];
	switch($level) {
	case 1:
		$SectionIndex[1]++;
		$SectionIndex[2] = 0;
		$SectionIndex[3] = 0;
		$SectionIndex[4] = 0;
		$fontSize = $Document['Section1Size'];
		$section = "" . $SectionIndex[1];
		break;
	case 2:
		$SectionIndex[2]++;
		$SectionIndex[3] = 0;
		$SectionIndex[4] = 0;
		$fontSize = $Document['Section2Size'];
		$section = $SectionIndex[1] . "." . $SectionIndex[2];
		break;
	case 3:
		$SectionIndex[3]++;
		$SectionIndex[4] = 0;
		$section = $SectionIndex[1] . "." . $SectionIndex[2] . "." . $SectionIndex[3];
		$fontSize = $Document['Section3Size'];
		break;
	case 4:
		$SectionIndex[4]++;
		$section = $SectionIndex[1] . "." . $SectionIndex[2] . "." . $SectionIndex[3] . "." . $SectionIndex[4];
		$fontSize = $Document['Section3Size'];
		break;
	default:
		$SectionIndex[1]++;
		$SectionIndex[2] = 0;
		$SectionIndex[3] = 0;
		$SectionIndex[4] = 0;
		$fontSize = $Document['Section1Size'];
		$section = $SectionIndex[1];
		break;
	}

	$title = "$section. $label";
	$this->AddTOC($title, $level-1);
		
   $this->SetFont('', 'B', $Document['FontSize']);
   $this->SetTextColor(0, 0, 0);	// Black
   $this->Cell(0, 6, $title, 0, 1, 'L', 0);
   $this->Ln(4);
   $this->SetFont('', '', $Document['FontSize']);
}

function LoadFile($file, $newPage)
{
	global $Document;
	global $Doclet;
	
	if($newPage) $this->AddPage();
   $Document['PartialPage'] = 0;
   
	$this->SetLeftMargin($Document['LeftMargin']);
	$indentLevel = 0;
	
   //Read text file
   $f = fopen($Doclet . $file, 'r');
   if($f == null) {
   	$this->Write($Document['LineSpace'], "Error: unable to open file '$file'");
   	return;
   }
   
  	$lineSpace = $Document['LineSpace'];
      
	while(!feof($f)) {
	   $buffer = fgets($f, 4096);
	   if(! $Document['ListMode']) {
	      $buffer = trim($buffer);
	      $addSpace = 1;
	   }
	   if(strlen($buffer) > 0 && strncmp($buffer, '<', 1) == 0) {	// Tag
	   	// Parse tag from line
	   	$tag = '';
	   	$part = explode('>', $buffer, 2);	// Extract start tag
	   	$tagPart = explode(' ', $part[0]);
	   	$tag = substr($tagPart[0], 1);
	   	
	   	// Extract tag argument - if present
	   	$tagArg = array();
	   	array_shift($tagPart);
         foreach($tagPart as $v) {
            if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$', $v, $argPart)) {
               $tagArg[strtolower($argPart[1])]=$argPart[2];
            }
         }
	   	
	   	// Extract text that follows tag - if present
	   	$text = '';
	   	if(count($part) > 1) {
	   		$part = explode('<', $part[1], 2);
	   		$text = $part[0];
	   	}
	   	// Act on tag
	   	switch($tag) {
	   	case 'b':	// Bold
	   		$this->SetFont('', 'B');
	   		$this->Write($Document['LineSpace'], $text);
	   		$this->SetFont('', '');
	   		break;
	   	case 'h1':	// Header - level 1
	   		$this->Section($text, 1);
	   		$addSpace = 0;
	   		break;
	   	case 'h2':	// Header - level 2
	   		$this->Section($text, 2);
	   		$addSpace = 0;
	   		break;
	   	case 'h3':	// Header - level 3
	   		$this->Section($text, 3);
	   		$addSpace = 0;
	   		break;
	   	case 'h4':	// Header - level 4
	   		$this->Section($text, 4);
	   		$addSpace = 0;
	   		break;
	   	case 'page':	// Page break
	   		$this->AddPage();
	   		$addSpace = 0;
	   		break;
	   	case 'pre':	// Pre-formatted
	   		$Document['ListMode'] = 1;
	   		$addSpace = 0;
   		  	$lineSpace = $Document['LineSpacePre'];
   		   $this->SetFont($Document['FontPre'], '', $Document['FontSizePre']);
	   		break;
	   	case '/pre':	// End of pre-formatting
	   		$Document['ListMode'] = 0;
 			 	$lineSpace = $Document['LineSpace'];	
			   $this->SetFont($Document['Font'], '', $Document['FontSize']);
	   		break;
	   	case 'center':	// Center justified text
	   		$this->CenterText($text);
	   		break;
	   	case 'dd':	// Indent
	   		$indentLevel++;
	   		$indent = $Document['LeftMargin'] + ($Document['Indent'] * $indentLevel);
	   		$this->SetLeftMargin($indent);
	   		$addSpace = 0;
	   		break;
	   	case '/dd':	// End of indent
	   		$indentLevel--;
	   		$indent = $Document['LeftMargin'] + ($Document['Indent'] * $indentLevel);
	   		$this->SetLeftMargin($indent);
	   		$this->Write($Document['LineSpace'], "\n");
	   		break;
	   	case 'br':	// Line break
	   		$this->Write($Document['LineSpace'], "\n");
	   		$addSpace = 0;
	   		break;
	   	case 'img':	// Image
	   		$this->Image($Doclet . $tagArg['src'], $this->GetX(), $this->GetY(), 
		      	170, 0);
	   		break;
	   	case 'a':	// Anchor (link)
	   		$this->SetTextColor(0, 0, 255);	// Blue
	   		$this->SetFont('', 'U');
	   		$this->Write($Document['LineSpace'], $text, $tagArg['href']);
	   		$this->SetFont('', '');
	   		$this->SetTextColor(0, 0, 0);	// Black
	   		break;
	   	}
	   } else {
	   	if(strlen($buffer) == 0) { $buffer = "\n\n"; $addSpace = 0; }
	      $this->Write($lineSpace, $buffer);
	   }
	   if($addSpace) $this->Write($Document['LineSpace'], ' ');
	}
   fclose($f);
}

function PageDefaults()
{
	global $Document;
	
	$this->SetFont($Document['Font'], '', $Document['FontSize']);
	$this->SetLeftMargin($Document['LeftMargin']);
	$this->SetRightMargin($Document['LeftMargin']);
  	$this->SetX($Document['LeftMargin']);
}

function EntryHeader($term, $type, $index)
{
	global $Document;
	
	if($index) $this->AddIndex($term, 1);
   $this->SetFillColor(200, 200, 200);	// Grey
   $this->SetTextColor(0, 0, 0);	// Black
   $this->Cell(80, $Document['LineSpaceDef'], $term, 0, 0, 'L', 1);
   $this->SetFillColor(255, 255, 255);	// White
   $this->SetTextColor(0, 0, 0);	// Black
   $this->Cell(80, $Document['LineSpaceDef'], $type, 0, 1, 'R', 1);
   $this->Line($Document['LeftMargin'], $this->GetY(), 180, $this->GetY());	// draw line
   $this->SetY($this->GetY() + 1);
}

function EntryText($text)
{
	global $Document;
	
	$this->SetRightMargin(25);
   $this->Write($Document['LineSpaceDef'], $text);
	$this->SetRightMargin(3);
}

function AddTOC($label, $level)
{
	global $TOC;
	
	$TOC[] = array("label" => $label, "level" => $level, "page" => $this->PageNo());
}

function PrintTOC()
{
	global $Document;
	global $TOC;
	
	$numWidth = $this->GetStringWidth("MMM");
	
	$this->AddPage();
	$Document['NumberRoman'] = 1;
	$this->SetFont($Document['Font'], 'B', $Document['FontSize']);
	$this->Write($Document['LineSpace'], "Table of Contents");
	$this->Ln();
	
	$this->SetFont($Document['Font'], '',$Document['FontSize']);
	$count = count($TOC);
	for($i = 0; $i < $count; $i++) {
		$level = $TOC[$i]['level'];
		$label = $TOC[$i]['label'];
		$page = $TOC[$i]['page'];
	   $this->Indent($level);
	   $this->Write($Document['LineSpace'], "$label ");
	   $x = $this->GetX();
	   while($x < 170) {
	   	$this->Write($Document['LineSpace'], ".");
	   	$x = $this->GetX();
	   }
	   $this->SetX(170 + ($numWidth - $this->GetStringWidth("$page")));	// Right justify
	   $this->Write($Document['LineSpace'], "$page");
	   $this->Ln();
	}
	$Document['NumberChange'] = 1;
	$Document['PartialPage'] = 1;
}

function AddIndex($label)
{
	global $IndexTemp;
	
	$IndexTemp[] = array("label" => $label, "page" => $this->PageNo());
}

function PrintIndex()
{
	global $Document;
	global $Index;
	
	sort($Index);
	
	$this->AddPage();
	$Document['Columns'] = 2;
	$this->Section("Index", 1);
   $this->PageTop = $this->GetY();  // Special case so items are below section header
	
	$count = count($Index);
	for($i = 0; $i < $count; $i++) {
		$label = $Index[$i]['label'];
		$page = $Index[$i]['page'];
	   $this->Write($Document['LineSpace'], "$label, $page");
	   $this->Ln();
	}
	$Document['Columns'] = 1;
}

function Indent($level)
{
	global $Document;
	$this->SetX($Document['LeftMargin'] + ($Document['Indent'] * $level));
}

function ResetPageCount()
{
	global $Document;
	
	$Document['PageOffset'] = $this->PageNo();
}

function MakeDictionary()
{
   global $Document;
   global $Version;
   global $Generation;

	$needLine = 0;
	
	// Query dictionary and create and entry for each item
	$query = "select" 
	       . " dictionary.*"
	       . " from dictionary"
	       . " where dictionary.Version = '" . $Version . "'"
	       . " order by dictionary.Term"
	       ;
	$result = mysql_query($query);
	if(!$result) {
	   $this->Write($Document['LineSpace'], "Error in query: " . $query);
	   $this->Output();
	   exit();
	}
	
	$this->AddPage();
	$this->PageDefaults();
	$this->SetFont('', '', $Document['FontSizeDef']);
	
	while(($row = mysql_fetch_object($result)) != null) {
		if($needLine) $this->Ln();
		$needLine = 1;
	   $this->Ln();
	   $this->EntryHeader($row->Term, $row->Type, 1);
	   $this->EntryText($row->Definition);
	   // $this->Ln();
	   // Sub-elements - Old style
		if(strlen($row->Elements) > 0) {
		   $this->Ln();
		   $this->Indent(1);
		   $this->Write($Document['LineSpace'], "Sub-elements: ");
		   $x = $this->GetX();
         $this->SetLeftMargin($x);
         // $this->SetX($x);
			$list = explode(",", $row->Elements);
			$n = count($list);
			for($i = 0; $i < $n; $i++) {
			   $this->Write($Document['LineSpaceDef'], $list[$i]);
			   $this->Ln();
		   }
		   $this->SetLeftMargin($Document['LeftMargin']);
		   $this->SetX($Document['LeftMargin']);
		   $needLine = 0;
		}

		// Show attributes - if any
		if(strlen($row->Attributes) > 0) {
   		$this->Ln();
		   $this->Indent(1);
		   $this->Write($Document['LineSpaceDef'], "Attributes: ");
		   $x = $this->GetX();
         $this->SetLeftMargin($x);
         // $this->SetX($x);
			$list = explode(",", $row->Attributes);
			$n = count($list);
			for($i = 0; $i < $n; $i++) {
			   $this->Write($Document['LineSpaceDef'], $list[$i]);
			   $this->Ln();
        	} 
		   $this->SetLeftMargin($Document['LeftMargin']);
		   $this->SetX($Document['LeftMargin']);
		   $needLine = 0;
	   }         
		
		if($Generation < 2) {
			// Show sub-elements - if any
			if(strlen($row->Elements) > 0) {
	   		$this->Ln();
			   $this->Indent(1);
			   $this->Write($Document['LineSpaceDef'], "Sub-elements: ");
			   $x = $this->GetX();
	         $this->SetLeftMargin($x);
	         // $this->SetX($x);
				$list = explode(",", $row->Elements);
				$n = count($list);
				for($i = 0; $i < $n; $i++) {
				   $this->Ln();
				   $this->Write($Document['LineSpaceDef'], $list[$i]);
	        	} 
			   $this->SetLeftMargin($Document['LeftMargin']);
			   $this->SetX($Document['LeftMargin']);
			   $needLine = 0;
		   }         
		} else { 	// Sub-elements
			$query2 = "select" 
			       . " ontology.*"
			       . " from ontology"
			       . " where ontology.Object = '" . $this->sqlencode($row->Term) . "'"
		          . " and ontology.Version='" . $Version  . "'"
			       . " order by ontology.Element"
			       ;
		
		   $result2 = mysql_query($query2);
		   if(!$result2) {
		          print "Error in query: " . $query2;
		          done();
		   }
				        
			$needHeader = 1;
		   while(($row2 = mysql_fetch_object($result2)) != null) {	
		   	if($needHeader) {		
				   $this->Ln();
			   	$this->Indent(1);
			   	$this->Write($Document['LineSpaceDef'], "Sub-elements: ");
			   	$x = $this->GetX();
	         	$this->SetLeftMargin($x);
	         	$needHeader = 0;
	         }
			   $this->Ln();
			   $this->Write($Document['LineSpaceDef'], $row2->Element);
			   $needLine = 0;
			}
			if(! $needHeader) {	// Finish out list
	   		$this->Ln();
		   	$this->SetLeftMargin($Document['LeftMargin']);
		   	$this->SetX($Document['LeftMargin']);
	   	}
	   	
	   	mysql_free_result($result2);
	   }
	
		// Referenced list
		if(strlen($row->List) > 0) {
			if($Generation < 2) {
	   		$this->Ln();
			   $this->Indent(1);
			   $this->Write($Document['LineSpaceDef'], 'see ' . $row->List . ' List');
			   $needLine = 0;
			} else {
		      $query2 = "select" 
		                . " list.*"
		                . " from list"
		                . " where list.Name = '" . $this->sqlencode($row->List)  . "'"
		                . " and list.Version='" . $Version  . "'"
		                . " order by list.Name"
		                ;
		
		      $result2 = mysql_query($query2);
		      if(!$result2) {
		              print "Error in query: " . $query2;
		              done();
		      }
		
				$needHeader = 1;
		      while(($row2 = mysql_fetch_object($result2)) != null) {
				   if($needHeader) {
				   	$this->Ln();
				   	$this->Indent(1);
				   	$this->Write($Document['LineSpaceDef'], "Allowed Values: ");
				   	$x = $this->GetX();
		         	$this->SetLeftMargin($x);
		         	$needHeader = 0;
		         }
		         
					if(strcmp($row2->Type, "Open") == 0) {
				   	$this->Ln();
				   	$this->Write($Document['LineSpaceDef'], "For a current list see $row2->Reference");				
				  	} else {				// Closed - query for allowed values
				  		$this->MakeEnum("", $row->List);
				  		/*
						$query3 = "select *" 
						       . " from member"
						       . " where member.List = '" . $this->sqlencode($row->List) . "'"
			                . " and member.Version='" . $Version  . "'"
						       . " order by member.Term"
						       ;
			
					   $result3 = mysql_query($query3);
					   if(!$result3) {
					          print "Error in query: " . $query3;
					          done();
					   }
							        
					   while(($row3 = mysql_fetch_object($result3)) != null) {	
						   $this->Ln();
						   $this->Write($Document['LineSpaceDef'], $row3->Term);
						}
				   	mysql_free_result($result3);
				   	*/
					}
					$needLine = 0;
				}
		   	mysql_free_result($result2);
				if(! $needHeader) {	// Finish out list
		   		$this->Ln();
			   	$this->SetLeftMargin($Document['LeftMargin']);
			   	$this->SetX($Document['LeftMargin']);
		   	}
			}
		}
	}
	
	// Clean-up
	mysql_free_result($result);
}

function MakeDataType()
{
   global $Document;
   global $Version;
   global $Generation;

	$needLine = 0;
	
	// Query data type table and create and entry for each item
	$query = "select" 
	       . " type.*"
	       . " from type"
	       . " where type.Version = '" . $Version . "'"
	       . " order by type.Name"
	       ;
	$result = mysql_query($query);
	if(!$result) {
	   $this->Write($Document['LineSpace'], "Error in query: " . $query);
	   $this->Output();
	   exit();
	}
	
	$this->SetFont('', '', $Document['FontSizeDef']);
	
	while(($row = mysql_fetch_object($result)) != null) {
		$this->SetFont('', 'B');
		$this->Write($Document['LineSpace'], "$row->Name");
		$this->SetFont('', '');
		$this->Write($Document['LineSpace'], ": $row->Description");
		$this->Ln();
		$this->Ln();
	}
	
	// Clean-up
	mysql_free_result($result);
}

// Determine if a dictionary term is an enumeration
// return the name of the enumeration list or "" is not an enumeration
function GetEnumeration($term)
{
	global $Version;
	
   $query = "select" 
          . " dictionary.*"
          . " from dictionary"
          . " where dictionary.Term = '" . $this->sqlencode($term)  . "'"
          . " and dictionary.Version='" . $Version  . "'"
          ;

   $result = mysql_query($query);
   if(!$result) {
      print "Error in query: " . $query;
      done();
   }
   
   $itIs = "";
   while(($row = mysql_fetch_object($result)) != null) {
   	$buffer = trim($row->Type);
   	if($buffer == 'Enumeration') $itIs = $row->List;
   }
	// Clean-up
   mysql_free_result($result);
   
   return $itIs;
}


function MakeEnum($prefix, $list)
{
   global $Document;
   global $Version;

	$query = "select" 
	       . " member.*"
	       . " from member"
	       . " where member.Version='" . $Version  . "'"
	       . " and member.List ='" . $this->sqlencode($list) . "'"
	       ;

	$result = mysql_query($query);
	if(!$result) {
	   print "Error in query: " . $query;
	   done();
	}
	   
	while(($row = mysql_fetch_object($result)) != null) {
		$term = $row->Term;
		$buffer = "";
		if(strlen($prefix) > 0) $buffer .= $prefix . ".";
		$buffer .= $term;
	   $this->Ln();
	   $this->Write($Document['LineSpaceDef'], $buffer);
		$enumList = $this->GetEnumeration($term);
		if(strlen($enumList) != 0) $this->MakeEnum($buffer, $enumList);
	}
	// Clean-up
   mysql_free_result($result);
}

function MakeMember($item)
{
	global $Document;
	global $Version;
	
	$w = array(50, 100);	// Cell widths
	
	$query = "select" 
	       . " list.*, member.Term, dictionary.Definition"
	       . " from list, member, dictionary"
	       . " where list.Name = member.List"
	       . " and member.Term = dictionary.Term"
	       . " and list.Name = '" . $item . "'"
          . " and list.Version='" . $Version  . "'"
          . " and dictionary.Version='" . $Version  . "'"
          . " and member.Version='" . $Version  . "'"
	       . " order by list.Name"
	       ;

	$result = mysql_query($query);
	if(!$result) {
	   $this->Write($Document['LineSpace'], "Error in query: " . $query);
	   $this->Output();
	   exit();
	}
			
	$first = 1;
	while(($row = mysql_fetch_object($result)) != null) {
	   if($first) {
	   	$first = 0;
	   	// Table header
		   $this->SetFillColor(220, 220, 220);	// Grey
		   $this->Indent(1);
		   $y = $this->GetY();
		   $this->Cell($w[0], 5, 'Term', 1, 0, 'C', 1); 
	      $x = $Document['LeftMargin'] + $Document['Indent'] + $w[0];
	      $this->SetLeftMargin($x);
         $this->SetX($x);
		   $this->SetY($y);
		   $this->Cell($w[1], 5, 'Definition', 1, 0, 'C', 1); 
		   $this->SetFillColor(255, 255, 255);	// White
		   $this->Ln();
	   }
	   // Table entry
	   $x = $Document['LeftMargin'] + $Document['Indent'];
	   $this->SetLeftMargin($x);
	   $this->SetX($x);
	   $this->Write($Document['LineSpace'], $row->Term);
	   $x = $Document['LeftMargin'] + $Document['Indent'] + $w[0];
	   $this->SetLeftMargin($x);
      $this->SetX($x);
	   $this->MultiCell($w[1], 5, $row->Definition, 0, 'L');
	}
}

function MakeList()
{
	global $Document;
	global $Version;
	global $Generation;
	
	$w = array(50, 100);	// Cell widths
	
	// Query dictionary and create and entry for each item
	if($Generation < 2) {
      $query = "select" 
          . " list.*"
          . " from list"
          . " where list.Version='" . $Version  . "'"
          . " order by list.Name"
          ;
	} else {
      $query = "select" 
             . " list.*"
             . " from list"
             . " where list.Version='" . $Version  . "'"
             . " order by list.Name"
             ;
	}
	
	$result = mysql_query($query);
	if(!$result) {
	   $this->Write($Document['LineSpace'], "Error in query: " . $query);
	   $this->Output();
	   exit();
	}
	
	$lastList = "";
	
	$this->AddPage();
	$this->PageDefaults();

	$this->LoadFile('list.txt', 0);
	
	$this->SetFont('', '', $Document['FontSizeDef']);
	while(($row = mysql_fetch_object($result)) != null) {
			$this->Ln();
		   $x = $Document['LeftMargin'];
		   $this->SetLeftMargin($x);
      	$this->SetX($x);
	   	$this->EntryHeader($row->Name . ' List', $row->Type, 1);
	   	$this->EntryText($row->Description);
	   	$lastList = $row->Name;
		   $this->Ln();
		   $this->Ln();
			if(strcmp($row->Type, "Open") == 0) {
				$this->Indent(1);
				$this->EntryText("For a current list see $row->Reference");
				$this->Ln();
			} else {	// Closed list
				$this->MakeMember($row->Name);
			}
	}
	$x = $Document['LeftMargin'];	
	$this->SetLeftMargin($x);
	$this->SetX($x);
	
	// Clean-up
	mysql_free_result($result);
}

	
function MakeHistory()
{
	global $Document;
	
	// Query dictionary and create and entry for each item
	$query = "select" 
	       . " history.*"
	       . " from history"
	       ;
	$result = mysql_query($query);
	if(!$result) {
	   $this->Write($Document['LineSpace'], "Error in query: " . $query);
	   $this->Output();
	   exit();
	}
	
	$lastVersion = "";
	
	$this->AddPage();
	$this->PageDefaults();
	$this->Section("Change History", 1);
	
	$this->SetFont('', '', $Document['FontSizeDef']);
	$w = array(30, 30, 100);
	while(($row = mysql_fetch_object($result)) != null) {
	   if(strcmp($lastVersion, $row->Version) != 0) {
		   $x = $Document['LeftMargin'];
		   $this->Ln();
		   $this->SetLeftMargin($x);
      	$this->SetX($x);
	   	$this->EntryHeader($row->Version, '', 0);
		   $lastVersion = $row->Version;
	   }
	   // Table entry
	   $x = $Document['LeftMargin'];
	   $this->SetLeftMargin($x);
	   $this->SetX($x);
	   $buffer = $row->Updated;
	   // if(strlen($buffer) == 0) $buffer = "Draft";
	   $this->Write($Document['LineSpaceDef'], $buffer);
	   $x = $Document['LeftMargin'] + $w[0];
	   // $this->SetLeftMargin($x);
      // $this->SetX($x);
	   // $this->Write($Document['LineSpaceDef'], $row->ChangedBy);
	   // $x = $Document['LeftMargin'] + $w[0] + $w[1];
	   $this->SetLeftMargin($x);
      $this->SetX($x);
	   // $this->Write($Document['LineSpaceDef'], $row->Description);
	   $this->MultiCell($w[2], $Document['LineSpaceDef'], $row->Description, 0, 'L');
	}
	$x = $Document['LeftMargin'];	
	$this->SetLeftMargin($x);
	$this->SetX($x);
	
	// Clean-up
	mysql_free_result($result);
}

function MakeTree()
{
	$this->AddPage();
	$this->PageDefaults();
	
	$this->LoadFile('tree.txt', 0);
	
	$this->ShowTree("Spase", 0, 1);
}

function ShowTree($term, $indent, $dictionary)
{
	global $Generation;
	
	if($Generation < 2) $this->ShowTree1($term, $indent, $dictionary);
	else $this->ShowTree2($term, $indent, 1, "", 0);
}

function ShowTree1($term, $indent, $dictionary)
{
	global $Version;
	global $Generation;
	
	$w = array(20, 25, 45, 30, 30, 30, 30);	// Cell width
	
	if($dictionary) {	// Dictionary term
	   $query = "select" 
	          . " dictionary.*"
	          . " from dictionary"
	          . " where dictionary.Term = '" . $this->sqlencode($term)  . "'"
	          . " and dictionary.Version = '" . $Version . "'"
	          ;
	} else {	// Members of a list
	   $query = "select" 
	          . " member.*"
	          . " from member"
	          . " where member.List = '" . $this->sqlencode($term)  . "'"
	          . " and member.Version = '" . $Version . "'"
	          ;
	}

   $result = mysql_query($query);
   if(!$result) {
      print "Error in query: " . $query;
      done();
   }
   
   while(($row = mysql_fetch_object($result)) != null) {
   	$endRow = 1;
		for($i = 0; $i < $indent; $i++) $this->Cell($w[$i], 5, "");
		if($dictionary) {
		   $this->Cell($w[$indent], 5, $row->Term);
			if(strlen($row->Elements) > 0) {
				$this->SetTextColor(0, 0, 255);	// Blue
			   $this->Cell($w[$indent], 5, "has elements");
			   $this->SetTextColor(0);	// Black
			   $endRow = 0;
			   $list = explode(",", $row->Elements);
			   $n = count($list);
			   for($i = 0; $i < $n; $i++) {
			   	$this->Ln();
			   	$this->ShowTree1(trim($list[$i]), $indent + 1, $dictionary);
			   }
			}
			if(strlen($row->List) > 0) {
				$this->SetTextColor(255, 0, 0);	// Red
			   $this->Cell($w[$indent], 5, "is");
			   $this->SetTextColor(0);	// Black
			   $endRow = 0;
		   	$this->ShowTree1($row->List, $indent + 1, 0);
			}
			break;	// Only do first term
		} else {	// Member list
			$this->Cell($w[$indent], 5, $row->Term);
		}		
		$this->Ln();
   }
	
	// Clean-up
   mysql_free_result($result);
}

function ShowTree2($term, $indent, $occur, $group, $pointer)
{
	global $ScriptName;
	global $Version;
	
	$rowList = array();
	if($pointer == 0) {	// Query for list
	   $query = "select" 
	          . " ontology.*"
	          . " from ontology"
	          . " where ontology.Object = '" . $this->sqlencode($term)  . "'"
	          . " and ontology.Version='" . $Version  . "'"
	          . " Order By ontology.Pointer"
	          ;
	
	   $result = mysql_query($query);
	   if(!$result) {
	      print "Error in query: " . $query;
	      done();
	   }
	   
	   $count = 0;
	   $showName = 1;
	   while(($row = mysql_fetch_object($result)) != null) {
	   	$rowList[] = $row;
	   }
		// Clean-up
	   mysql_free_result($result);
	}
	// if($pointer == -1) { $showName = 0; $pointer = 0; }
 
   $nRow = count($rowList);
   if($nRow == 0) {
   	$this->PrintTerm($term, $indent, $occur, $group, 0, 0);
		$this->Ln();
   	return 0;
   }
   
   for($i = 0; $i < $nRow; $i++) {
   	$row = $rowList[$i];
   	$endRow = 1;
		
		// Print object name
		if($showName) {
			$this->PrintTerm($row->Object, $indent, $occur, $group, 0, 1);
			$this->Ln();
			$showName = 0;
		}

		// Show elements
		$this->ShowTree2($row->Element, $indent+1, $row->Occurence, $row->Group, 0);
   }
	
   return $count;
}

function PrintTerm($term, $indent, $occur, $group, $pointer, $hasElements)
{
	global $Version;
	global $Document;

   $ofGroup = $this->GetOfGroup($group);
   	
	$buffer = "";
	if($pointer) $buffer = ' [ID]';
	if($occur != -1) $buffer = $buffer . ' (' . $occur . $ofGroup . ')';
	for($i = 0; $i < $indent; $i++) $this->Cell(10, 5, "|");
   $this->Cell(80, $Document['LineSpace'], '+ '. $term . $buffer);
	if($hasElements) {
		$this->SetTextColor(0, 0, 0);	// Black
	}

}

function GetOfGroup($group)
{
	global $GroupIndex;
	$n = 'A';
	
	if($group == "") return "";
	
   foreach ($GroupIndex as $key=>$value)
   {
   	if($value == $group) return " of " . $n;
      $n++;
   }
   
   // If we reach here - not in array, add it
   $GroupIndex[] = $group;
   return " of " . $n;
}

function CenterText($text)
{
	$this->Cell(0, 8, $text, 0, 1, 'C');
}

function TitlePage()
{
	global $Document;
	global $Version;
	global $ReleaseDate;
	
	$this->SetFont('', 'B', $Document['FontSize']+4);
	$this->AddPage();
	$this->LoadFile('title.txt', 0);
	$this->Ln();

	$this->CenterText('Version: ' . $Version);
	
	$this->SetFont('', '', $Document['FontSize']);
	$this->CenterText('Release Date: ' . $ReleaseDate);
	// if(strcmp($ReleaseDate, "Draft") == 0) {
	   $this->CenterText('Document Generated: ' . date('Y-M-d'));
	// }
	$this->Ln();
}

function sqlencode($term)
{
	return mysql_escape_string($term);
}

function ArabicToRoman( $myArabicNum, $htmlelement = false, $htmlelement2 = '</u>', $dontOptimise = false ) {
    if( is_bool( $htmlelement ) ) {
        $dontOptimise = $htmlelement;
        $htmlelement = '<u style="text-decoration:overline;" style=&{\'text-decoration:underline;\'};>';
    }
    if( $myArabicNum != floor( $myArabicNum ) ) { return "not convertable, number is not an integer."; }
    if( $myArabicNum > 3999999 ) { return "not convertable, number exceeds 3999999."; }
    if( $myArabicNum <= 0 ) { return "not convertable, number must be greater than 0."; }
    //prepare roman numerals
    $ar_to_rom[1000000] = $htmlelement."M".$htmlelement2;
    $ar_to_rom[500000] = $htmlelement."D".$htmlelement2;
    $ar_to_rom[100000] = $htmlelement."C".$htmlelement2;
    $ar_to_rom[50000] = $htmlelement."L".$htmlelement2;
    $ar_to_rom[10000] = $htmlelement."X".$htmlelement2;
    $ar_to_rom[5000] = $htmlelement."V".$htmlelement2;
    $ar_to_rom[1000] = "M";
    $ar_to_rom[500] = "D";
    $ar_to_rom[100] = "C";
    $ar_to_rom[50] = "L";
    $ar_to_rom[10] = "X";
    $ar_to_rom[5] = "V";
    $ar_to_rom[1] = "I";
    $myRomanNum = '';
    for( $x = 1000000; $x >= 1; $x /= 10 ) {
        //start at M(bar) and work down in factors of 10 to 1.
        //45xxx, 49..9xx, 49..95xx, 99..95xx and 99..9xx can be done with only two
        //characters. This function optimises the output. Not doing this
        //would result in the use of 4+ characters instead
        switch( floor( $myArabicNum / $x ) ) {
            case 1 :
                $myRomanNum .= $ar_to_rom[$x];
                break;
            case 2 :
                $myRomanNum .= $ar_to_rom[$x].$ar_to_rom[$x];
                break;
            case 3 :
                $myRomanNum .= $ar_to_rom[$x].$ar_to_rom[$x].$ar_to_rom[$x];
                break;
            case 4 :
                if( $dontOptimise ) {
                    $myRomanNum .= $ar_to_rom[$x].$ar_to_rom[5 * $x];
                } else {
                    //optimise for 45xx, 49..95xx and 99..9xx, work out the number of 9s in a row.
                    $num_nines = 1; $i2 = 0; $subnum = '0.';
                    for( $i = 1; substr( $myArabicNum, $i, 1 ) == '9'; $i++ ) { $num_nines *= 10; $i2 = $i; }
                    for( $i = 1; $i <= $i2; $i++ ) { $subnum .= '9'; }
                    if( substr( $myArabicNum, $i2+1, 1 ) == '5' ) {
                        //any number of nines in a row followed by a 5 (including no 9s)
                        $myRomanNum .= $ar_to_rom[$x / ( 2 * $num_nines )].$ar_to_rom[5 * $x];
                        $subnum .= '5';
                    } else {
                        //any number of nines in a row (including no 9s)
                        $myRomanNum .= $ar_to_rom[$x / $num_nines].$ar_to_rom[5 * $x];
                    }
                    $myArabicNum -= $subnum * $x;
                }
                break;
            case 5 :
                $myRomanNum .= $ar_to_rom[5 * $x];
                break;
            case 6 :
                $myRomanNum .= $ar_to_rom[5 * $x].$ar_to_rom[$x];
                break;
            case 7 :
                $myRomanNum .= $ar_to_rom[5 * $x].$ar_to_rom[$x].$ar_to_rom[$x];
                break;
            case 8 :
                $myRomanNum .= $ar_to_rom[5 * $x].$ar_to_rom[$x].$ar_to_rom[$x].$ar_to_rom[$x];
                break;
            case 9 :
                if( $dontOptimise ) {
                    $myRomanNum .= $ar_to_rom[$x].$ar_to_rom[10 * $x];
                } else {
                    //optimise for 99..95xx and 99..9xx, work out the number of 9s in a row.
                    $subnum = '0.'; $num_nines = 0.1; //trust me, it works
                    for( $i = 0; substr( $myArabicNum, $i, 1 ) == '9'; $i++ ) { $num_nines *= 10; $i2 = $i + 1; }
                    for( $i = 1; $i < $i2; $i++ ) { $subnum .= '9'; }
                    if( substr( $myArabicNum, $i2, 1 ) == '5' ) {
                        //any number of nines in a row followed by a 5 (including only one 9)
                        $myRomanNum .= $ar_to_rom[$x / ( 2 * $num_nines )].$ar_to_rom[10 * $x];
                        $subnum .= '5';
                    } else {
                        //any number of nines in a row (including only one 9)
                        $myRomanNum .= $ar_to_rom[$x / $num_nines].$ar_to_rom[10 * $x];
                    }
                    $myArabicNum -= $subnum * $x;
                }
                break;
        }
        //take out the bit we just did and go round again with the remainder
        $myArabicNum %= $x;
    }
    return $myRomanNum;
}

function MakeDocument()
{
	global $Version;
	global $IndexTemp;
	
	$IndexTemp = array();

	$this->LoadFile('overview.txt', 1);
	$this->LoadFile('intro.txt', 1);
	// $this->LoadFile('model.txt', 0);
	$this->MakeTree();
	$this->LoadFile('note.txt', 1);
	$this->LoadFile('examples.txt', 1);
	$this->LoadFile('definitions.txt', 1);
	if(strcmp($Version, "2.0.0") < 0) $this->MakeDataType();
	$this->MakeDictionary();
	$this->MakeList();
	$this->LoadFile('appendix-a.txt', 1);
	$this->LoadFile('biblio.txt', 1);
	$this->PrintIndex();
	$this->MakeHistory();
}

}

function SaveIndex()
{
	global $IndexTemp;
	global $Index;
	
	$Index = $IndexTemp;
}

function ResetCounters()
{
	global $SectionIndex;
	
	$SectionIndex[1] = 0;
	$SectionIndex[2] = 0;
	$SectionIndex[3] = 0;
}

function OpenDatabase()
{
	global $DatabaseConn;
	
	// Database access variables
	global $Host;
	global $Database;
	global $Username;
	global $Password;
	
	// Connect to database server
	$DatabaseConn = mysql_pconnect($Host, $Username, $Password);
	if(!$DatabaseConn) {
	   print "Unable to connect to database server.";
	   exit();
	}
	
	if(!mysql_select_db($Database)) {
	   print "Unable to connect to database: " . $Database;
	   exit();
	}
}

function CloseDatabase()
{
	global $DatabaseConn;
	
}

function GetVersion()
{
	global $Version;
	global $ReleaseDate;
	global $Doclet;
	global $Generation;

        $ReleaseDate = "";
	
	// Query database for information on choosen version
	if(isset($Version)) {
		$query = "select"
	   		. " * "
	   		. " from history"
	   		. " where"
	   		. " history.ID = (Select max(history.ID) from history where Version = '$Version')"
		      ;
	
		$result = mysql_query($query);
	   if(!$result) {
	      print "Error in query: " . $query;
	      done();
	   }
	   
	   while(($row = mysql_fetch_object($result)) != null) {
	      if($row->Description == "Released.") $ReleaseDate = $row->Updated;
	   }
	}
	
	// Get most recent version if version is not set
	if(! isset($Version) || strlen($Version) == 0) {
		$query = "select"
	   		. " * "
	   		. " from history"
	   		. " where"
	   		. " history.ID = (Select max(history.ID) from history)"
		      ;
	
		$result = mysql_query($query);
	   if(!$result) {
	      print "Error in query: " . $query;
	      done();
	   }
	   
	   while(($row = mysql_fetch_object($result)) != null) {
	      $Version = $row->Version;
	      if($row->Description == "Released") $ReleaseDate = $row->Updated;
	   }
	}
	
	if(strlen($ReleaseDate) == 0) $ReleaseDate = "Draft";

	// Set generation
	$Generation = 2;	// Newest
	if(strcmp($Version, "0.99.1") == 0) $Generation = 1;
	if(strcmp($Version, "0.99.2") == 0) $Generation = 1;
	if(strcmp($Version, "0.99.3") == 0) $Generation = 1;
	
	// Set source for doclet information
	$Doclet = "/var/www/spase/root/data/doclet/Version_" . str_replace(".", "_", $Version) . "/";
}

// Main 

// Determine version
if(isset($_REQUEST['version'])) { $Version = $_REQUEST['version']; }

if($argc > 1) $Version = $argv[1];

$Generation = 2;	// Newest

OpenDatabase();

GetVersion();

ResetCounters();

// Create and initialize PDF document
$pdf = new PDF();
$pdf->SetTitle($Document['Title']);
$pdf->SetAuthor($Document['Author']);
$pdf->PageDefaults();

// Create the document - this generates a table of contents
// $pdf->LoadFile('test.txt', 1);

$pdf->MakeDocument();

$pdf->Close();

ResetCounters();

// Now do it again and print TOC first
// Create and initialize PDF document
$pdf = new PDF();
$pdf->SetTitle($Document['Title']);
$pdf->SetAuthor($Document['Author']);
$pdf->PageDefaults();

$Document['ShowTrim'] = 0;
$Document['NumberRoman'] = 1;
$pdf->TitlePage();
$pdf->ResetPageCount();

// Preface material
$pdf->LoadFile('contrib.txt', 1);

$pdf->PrintTOC();

$pdf->ResetPageCount();
SaveIndex();

$pdf->MakeDocument();

$pdf->Output();

$pdf->Close();

?> 
