import { Stack, Typography } from "@mui/material";
import { Chip as MuiChip, styled } from "@mui/material";
import DispositionStatusComponent from "./DispositionStatusComponent";
import { green } from "@mui/material/colors";

import { formatDateTime } from "@/utils/date-time-formatters";
import { usePage } from "@inertiajs/react";
import { hasRole } from "@/utils/AccessManager";

export const DispositionComponent = ({ disposition }) => {
    // get auth from props
    const { auth } = usePage().props;

    // check if admin or bdm is logged in
    const isAdminOrBdm = hasRole(auth, [
        "Admin",
        "Business Development Manager",
    ]);

    return (
        <Stack direction="column" spacing={0.5} alignItems="flex-end">
            <Stack direction="row" spacing={0.5}>
                {disposition?.phone && (
                    <Typography variant="body2" color="primary.main">
                        {isAdminOrBdm && `${disposition.phone} - `}
                    </Typography>
                )}
                {disposition && (
                    <DispositionStatusComponent label={disposition?.status} />
                )}
            </Stack>
            {disposition?.updatedAt && (
                <Chip
                    clickable
                    size="small"
                    label={formatDateTime(disposition?.updatedAt)}
                    sx={{ borderRadius: 1 }}
                />
            )}
        </Stack>
    );
};

const Chip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${green[100]};
    color: ${green[800]};
    &:hover {
        background-color: ${green[200]};
        color: ${green[900]};
    }
`;
