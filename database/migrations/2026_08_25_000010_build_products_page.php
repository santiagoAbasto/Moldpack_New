<?php

use App\Models\ContentItem;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $page = Page::firstOrCreate(['slug' => 'productos'], ['name' => 'Productos', 'is_published' => true]);
        $section = $page->sections()->firstOrCreate(['type' => 'products'], ['title' => 'Productos', 'sort_order' => 0, 'is_visible' => true]);
        $section->update(['settings' => ['families' => [
            ['name' => 'Pirotines para cupcakes', 'children' => ['Surtidos','Nuevos Motivos','Halloween','Abstractos','Motivos','Shabby Chic','Sports','Animals','Ocasiones y Eventos','Lisos','Love','Lunares Chicos','Lunares Grandes','Metalizados']],
            ['name' => 'Cajas', 'children' => []], ['name' => 'Soportes', 'children' => []], ['name' => 'Tulipas para muffins', 'children' => []], ['name' => 'Descartables', 'children' => []], ['name' => 'Papel parafinado', 'children' => []], ['name' => 'Navidad', 'children' => []],
        ]]]);
        $products = [
            ['Psicodélico Multi-color','ABSTRACTOS','Nº10 U/510 | Nº8 U/510','psychedelic.png'], ['Remolino Blanco y Rojo','ABSTRACTOS','Nº10 U/510 | Nº8 U/510','remolino-rojo.png'], ['Náutico','MOTIVOS','Nº8 U/500','nautico.png'],
            ['Remolino Azul y Rojo','ABSTRACTOS','Nº10 U/510 | Nº8 U/510','remolino-azul-rojo.png'], ['¡Piratas! Calavera','MOTIVOS','Nº8 U/500','piratas.png'], ['Luna Celeste Argentina','MOTIVOS','Nº8 U/500','luna-celeste.png'],
            ['Luna Rosada','MOTIVOS','Nº8 U/500','luna-rosada.png'], ['Sports ¡Fans del deporte!','MOTIVOS','Nº8 U/500','sports.png'], ['¡Piratas! Niñas','MOTIVOS','Nº8 U/500','piratas-ninas.png'],
            ['Autos','MOTIVOS','Nº8 U/500','autos.png'], ['Rombos Multicolor','ABSTRACTOS','Nº10 U/510 | Nº8 U/510','rombos.png'], ['Remolino Azul y Amarillo','ABSTRACTOS','Nº10 U/510 | Nº8 U/510','remolino-amarillo.png'],
        ];
        foreach ($products as $order => [$title,$subcategory,$presentation,$file]) {
            $slug = str($title)->ascii()->slug()->toString();
            $item = $section->items()->updateOrCreate(['title' => $title], ['subtitle' => 'PIROTINES / '.$subcategory, 'label' => $presentation, 'url' => '/productos/'.$slug, 'sort_order' => $order, 'is_visible' => true, 'settings' => ['slug'=>$slug,'family'=>'Pirotines para cupcakes','subcategory'=>ucfirst(strtolower($subcategory)),'design'=>'Pirotín con diseño '.strtolower($subcategory).'. Una propuesta original para presentar tus creaciones.','food_safe'=>'Como todos los productos de MoldPack, se adhiere a las normas establecidas para envases de alimentos y packs gastronómicos, por el uso de lámina de parafina apta para el consumo.','quality'=>'MoldPack resuelve complementos gastronómicos para los más exigentes cocineros, impresos en papeles especiales para alimentos y en diferentes formatos.']]);
            Media::where('mediable_type', ContentItem::class)->where('mediable_id', $item->id)->delete();
            Media::create(['mediable_type'=>ContentItem::class,'mediable_id'=>$item->id,'kind'=>'image','disk'=>'public','path'=>'assets/figma/exact/products/'.$file,'alt'=>$title,'sort_order'=>0]);
        }
    }
    public function down(): void {}
};
