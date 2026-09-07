import { Head, usePage } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { NeuButton } from '@/components/neu/neu-button';
import type { AdminPlate } from '@/types/admin';

type PageProps = {
    plates: AdminPlate[];
    brandOperator: string;
};

/**
 * A single plate (from AdminQrPrintController) or a bulk sheet (from
 * AdminQrSheetController) — same page, same print CSS. Deliberately not
 * wrapped in AdminLayout: this page is meant to be printed standalone,
 * with no header/nav chrome. See plan/phases/phase-08-admin-qr.md M8.5.
 *
 * Plate dimensions are 50×50mm (the plan's stated default) at real-world
 * scale — `@page` + physical `mm` units, not a responsive/relative
 * layout, since what matters here is what comes out of the printer, not
 * what fits a viewport. `print-color-adjust: exact` keeps the QR pure
 * black on pure white rather than browsers lightening it to save ink.
 */
export default function AdminQrPrint() {
    const { plates, brandOperator } = usePage<PageProps>().props;

    return (
        <div className="bg-neu-surface min-h-screen p-6 print:bg-white print:p-0">
            <Head title="Print QR Plates" />

            <style>{`
                @page { size: A4; margin: 10mm; }

                @media print {
                    .no-print { display: none !important; }
                    * { box-shadow: none !important; text-shadow: none !important; }
                    body { background: #fff !important; }
                }

                .plate {
                    width: 50mm;
                    height: 50mm;
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }

                .plate img {
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }
            `}</style>

            <div className="no-print mb-6 flex items-center justify-between">
                <h1 className="text-xl font-bold">
                    {plates.length === 1
                        ? `Print Plate — ${plates[0].code}`
                        : `Print Sheet — ${plates.length} Plates`}
                </h1>
                <NeuButton
                    variant="primary"
                    className="gap-2"
                    onClick={() => window.print()}
                >
                    <Printer className="size-4" />
                    Print
                </NeuButton>
            </div>

            <div className="flex flex-wrap gap-[5mm]">
                {plates.map((plate) => {
                    const shortCode = plate.code
                        .replace(new RegExp(`^${plate.highway}-?`), '')
                        .replace(/-/g, ' ');

                    return (
                        <div
                            key={plate.publicId}
                            className="plate flex flex-col items-center justify-between border border-black bg-white p-[2mm] text-black"
                        >
                            <div className="text-center leading-tight">
                                <p className="text-[3mm] font-bold">
                                    {plate.highway}
                                </p>
                                <p className="text-[2.4mm] font-bold">
                                    {shortCode}
                                </p>
                            </div>
                            <img
                                src={plate.qrDataUri}
                                alt={`QR code for ${plate.code}`}
                                className="h-[30mm] w-[30mm]"
                            />
                            <div className="text-center leading-tight">
                                <p className="text-[2mm] font-bold">
                                    SCAN QR CODE
                                </p>
                                <p className="text-[1.8mm]">{brandOperator}</p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
