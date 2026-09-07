import { Head, Link, usePage } from '@inertiajs/react';
import { Download, ExternalLink, Printer } from 'lucide-react';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import AdminLayout from '@/layouts/admin/admin-layout';
import { download, print } from '@/routes/admin/stations/qr';

type PageProps = {
    stationPublicId: string;
    stationCode: string;
    encodedUrl: string;
    previewDataUri: string;
};

/**
 * plan/phases/phase-08-admin-qr.md M8.4.
 */
export default function AdminQrPreview() {
    const { stationPublicId, stationCode, encodedUrl, previewDataUri } =
        usePage<PageProps>().props;

    return (
        <AdminLayout active="stations">
            <Head title={`QR — ${stationCode}`} />

            <h1 className="mb-4 text-xl font-bold">QR — {stationCode}</h1>

            <NeuCard className="mx-auto max-w-md space-y-4 p-6 text-center">
                <img
                    src={previewDataUri}
                    alt={`QR code for ${stationCode}`}
                    className="mx-auto size-64"
                />

                <p className="text-neu-ink-muted font-mono text-sm break-all">
                    {encodedUrl}
                </p>

                <a
                    href={encodedUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-neu-link inline-flex items-center gap-1 text-sm font-medium"
                >
                    <ExternalLink className="size-4" />
                    Test this URL
                </a>

                <div className="flex flex-wrap justify-center gap-3">
                    <a
                        href={
                            download({ station: stationPublicId }).url +
                            '?format=png'
                        }
                    >
                        <NeuButton variant="primary" className="gap-2">
                            <Download className="size-4" />
                            PNG
                        </NeuButton>
                    </a>
                    <a
                        href={
                            download({ station: stationPublicId }).url +
                            '?format=svg'
                        }
                    >
                        <NeuButton variant="primary" className="gap-2">
                            <Download className="size-4" />
                            SVG
                        </NeuButton>
                    </a>
                    <Link href={print({ station: stationPublicId }).url}>
                        <NeuButton variant="ghost" className="gap-2">
                            <Printer className="size-4" />
                            Print Plate
                        </NeuButton>
                    </Link>
                </div>
            </NeuCard>
        </AdminLayout>
    );
}
