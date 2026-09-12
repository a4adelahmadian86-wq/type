<?php
namespace App\Http\Controllers;
use App\Models\TypingDocument;use Illuminate\Http\Request;use PhpOffice\PhpWord\PhpWord;use PhpOffice\PhpWord\IOFactory;use Dompdf\Dompdf;
class ExportController extends Controller {
 public function export(Request $r,string $format){$id=$r->validate(['document_id'=>'required|integer'])['document_id'];$doc=TypingDocument::where('id',$id)->where('user_id',auth()->id())->firstOrFail();abort_unless($doc->status!=='deleted',404);if($format==='docx')return $this->docx($doc);$pdf=new Dompdf();$html='<html dir="rtl"><head><meta charset="utf-8"></head><body style="font-family:DejaVu Sans;font-size:16px;direction:rtl">'.nl2br(e($doc->content)).'</body></html>';$pdf->loadHtml($html,'UTF-8');$pdf->setPaper('A4');$pdf->render();return response($pdf->output(),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="farast-'.$doc->id.'.pdf"']);}
 private function docx(TypingDocument $doc){$w=new PhpWord();$w->setDefaultFontName('B Nazanin');$w->setDefaultFontSize(16);$s=$w->addSection(['pageSizeW'=>11906,'pageSizeH'=>16838]);$p=$s->addTextRun(['alignment'=>'right']);$p->addText($doc->content,['name'=>'B Nazanin','size'=>16]);$tmp=tempnam(sys_get_temp_dir(),'farast');IOFactory::createWriter($w,'Word2007')->save($tmp);return response()->download($tmp,'farast-'.$doc->id.'.docx')->deleteFileAfterSend(true);}
}
