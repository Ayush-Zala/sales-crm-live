import { Stack } from "@mui/material";

import { useLayoutStore } from "@/store/layout-store";
import { MainHeader } from "./components/main-header";
import { MainSidebar } from "./components/main-sidebar";
import { DrawerHeader, Main } from "@/theme/styles";
import { Toaster } from "react-hot-toast";
import { ConfirmProvider } from "material-ui-confirm";
import MeetingAlertDialog from "@/Components/meeting-alert-dialog";
import { usePage } from "@inertiajs/react";
import { hasRole } from "@/utils/AccessManager";

export default function Authenticated({ user, children }) {
    const open = useLayoutStore((state) => state.open);

    const { auth } = usePage().props;

    const isSalesOrBDTL = hasRole(auth, [
        "Sales Executives",
        "Business Development Team Lead",
    ]);

    return (
        <Stack direction="row">
            <ConfirmProvider
                defaultOptions={{
                    confirmationButtonProps: { autoFocus: true },
                }}
            >
                <Toaster position="top-right" />
                <MainHeader user={user} />
                <MainSidebar />
                <Main open={open}>
                    <DrawerHeader />

                    {/* Only for Sales Executives   */}
                    {isSalesOrBDTL && <MeetingAlertDialog />}

                    {children}
                </Main>
            </ConfirmProvider>
        </Stack>
    );
}
