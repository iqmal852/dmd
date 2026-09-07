import { Form, Head, usePage } from '@inertiajs/react';
import { Lock, ShieldCheck } from 'lucide-react';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import UnlockSubmitController from '@/actions/App/Http/Controllers/Dossier/UnlockSubmitController';

type PageProps = {
    stationPublicId: string;
    stationCode: string;
    error: string | null;
    redirect: string | null;
};

/**
 * The password gate — shown only when DOSSIER_ACCESS_MODE=password or the
 * station has its own access_password. Nothing about the station beyond
 * its printed code appears here: no coordinates, no photos, no map. See
 * plan/phases/phase-03-dossier-shell.md M3.3.
 */
export default function DossierUnlock() {
    const { branding } = usePage().props;
    const { stationPublicId, stationCode, error, redirect } =
        usePage<PageProps>().props;

    return (
        <div className="bg-neu-surface text-neu-ink flex min-h-screen items-center justify-center p-6">
            <Head title={`Unlock ${stationCode}`} />

            <NeuCard className="w-full max-w-sm space-y-6 p-6">
                <div className="flex flex-col items-center gap-2 text-center">
                    <ShieldCheck
                        className="text-neu-primary-bright size-8"
                        aria-hidden="true"
                    />
                    <p className="text-sm font-bold tracking-wide uppercase">
                        {branding.operator}
                    </p>
                    <p className="text-lg font-bold">{stationCode}</p>
                    <p className="text-neu-ink-muted text-sm">
                        This dossier is password protected. Enter the password
                        to continue.
                    </p>
                </div>

                <Form
                    {...UnlockSubmitController.form({
                        station: stationPublicId,
                    })}
                    resetOnError={['password']}
                >
                    {({ processing, errors }) => (
                        <div className="space-y-4">
                            <input
                                type="hidden"
                                name="redirect"
                                value={redirect ?? ''}
                            />

                            <label className="block space-y-1.5">
                                <span className="text-neu-ink-muted flex items-center gap-1.5 text-sm font-medium">
                                    <Lock
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Password
                                </span>
                                <input
                                    type="password"
                                    name="password"
                                    autoFocus
                                    required
                                    className="neu bg-neu-surface text-neu-ink shadow-neu-inset-sm focus-visible:ring-neu-primary-bright w-full rounded-[var(--radius-neu-md)] px-4 py-2.5 outline-none focus-visible:ring-2"
                                />
                                {errors.password && (
                                    <span className="text-neu-danger block text-sm font-medium">
                                        {errors.password}
                                    </span>
                                )}
                            </label>

                            {error && (
                                <p className="text-neu-danger text-sm font-medium">
                                    {error}
                                </p>
                            )}

                            <NeuButton
                                type="submit"
                                variant="primary"
                                disabled={processing}
                                className="w-full"
                            >
                                Unlock
                            </NeuButton>
                        </div>
                    )}
                </Form>
            </NeuCard>
        </div>
    );
}
