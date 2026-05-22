<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Services\RagService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            'Política de devoluciones' => "# Política de Devoluciones NovaRetail\n\nLas devoluciones de productos tecnológicos se aceptan dentro de los 30 días posteriores a la compra con factura vigente.\n\n## Reglas principales\n- El equipo debe incluir caja y accesorios.\n- Equipos con daño físico no aplican, salvo defecto de fábrica reportado en las primeras 48 horas.\n- El reembolso puede realizarse a método original o nota de crédito.\n- Productos en promoción pueden tener políticas especiales informadas al momento de compra.",
            'Política de garantías' => "# Política de Garantías NovaRetail\n\nLa garantía estándar para laptops y smartphones es de 12 meses con cobertura por fallas de fabricación.\n\n## Cobertura\n- Diagnóstico técnico inicial en máximo 72 horas hábiles.\n- Reparación en centros autorizados.\n- Reemplazo si no hay reparación viable dentro de 20 días hábiles.\n\n## Exclusiones\n- Daño por líquidos\n- Golpes o manipulación no autorizada\n- Uso con voltaje incorrecto",
            'Escalamiento de reclamos' => "# Protocolo de Escalamiento de Reclamos\n\nNivel 1: Agente de atención en tienda o canal digital. Tiempo de respuesta objetivo: 4 horas hábiles.\nNivel 2: Supervisor regional cuando el caso no se resuelve en 24 horas.\nNivel 3: Comité de experiencia del cliente para casos críticos o riesgos reputacionales.\n\nTodo reclamo debe registrarse con número de ticket y evidencia adjunta en el sistema interno CRM.",
            'Procedimiento logístico' => "# Procedimiento Logístico Omnicanal\n\nLos pedidos web deben confirmarse en inventario en menos de 15 minutos.\n\nFlujo:\n1. OMS valida stock por tienda o centro de distribución.\n2. Se asigna preparación de pedido (picking y packing).\n3. Se genera guía de despacho con trazabilidad.\n4. El SLA de entrega estándar es 24-72 horas según ciudad.\n\nEn caso de ruptura de stock se notifica al cliente con alternativa o reembolso.",
            'Atención al cliente' => "# Manual de Atención al Cliente\n\nLa atención debe mantener tono profesional, empático y resolutivo.\n\nIndicadores:\n- FCR (First Contact Resolution) objetivo: 75%\n- CSAT objetivo mensual: 90%\n- Tiempo medio de respuesta en chat: menor a 2 minutos\n\nTodo contacto debe cerrar con resumen de acciones y próximos pasos para el cliente.",
        ];

        foreach ($documents as $title => $content) {
            $filename = Str::slug($title).'.md';
            $path = 'documents/'.$filename;

            Storage::disk('local')->put($path, $content);

            $document = Document::query()->updateOrCreate(
                ['title' => $title],
                [
                    'filename' => $filename,
                    'path' => $path,
                    'mime_type' => 'text/markdown',
                    'status' => 'pending',
                    'metadata' => ['seeded' => true],
                ]
            );

            if ((string) config('services.gemini.api_key') !== '') {
                try {
                    app(RagService::class)->ingestDocument($document);
                } catch (\Throwable $exception) {
                    Log::error('Document seeding ingest failed', [
                        'title' => $title,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }
    }
}
