import { Drawer, Stack, Typography } from "@mui/material";

import { DrawerHeader } from "@/theme/styles";
import { PatternsLogo } from "@/assets/patterns-log";
import { useLayoutStore } from "@/store/layout-store";
import SidebarMenuList from "@/Components/SidebarMenuList";

export const MainSidebar = () => {
    const open = useLayoutStore((state) => state.open);
    const toggle = useLayoutStore((state) => state.toggle);

    return (
        <Drawer
            id="main-siderbar"
            variant="permanent"
            open={open}
            onClose={toggle}
        >
            <DrawerHeader
                sx={{
                    borderBottom: (theme) =>
                        `1px solid ${theme.palette.divider}`,
                }}
            >
                <Stack direction="row" spacing={1} alignItems="center">
                    <PatternsLogo width={45} height={45} />
                    <Typography variant="h5" fontWeight={700}>
                        {import.meta.env.VITE_APP_NAME}
                    </Typography>
                </Stack>
            </DrawerHeader>
            <SidebarMenuList />
        </Drawer>
    );
};
