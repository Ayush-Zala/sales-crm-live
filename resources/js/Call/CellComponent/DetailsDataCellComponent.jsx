import { hasPermission } from "@/utils/AccessManager";
import { formatDateTime } from "@/utils/date-time-formatters";
import { usePage } from "@inertiajs/react";
import { CallMadeRounded, DraftsTwoTone } from "@mui/icons-material";
import { Stack, Typography } from "@mui/material";

const DetailsDataCellComponent = ({ row, column }) => {
    const { auth } = usePage().props;

    switch (column.id) {
        case "name":
            const fullName = `${row.fname} ${row.lname ? row.lname : ""}`;
            return (
                fullName && (
                    <Stack direction="row" spacing={1}>
                        <Typography fontSize={16} color="primary.main">
                            {fullName}
                        </Typography>
                    </Stack>
                )
            );
        case "mail":
            const clientEmail = row.mail;
            return (
                clientEmail && (
                    <Stack direction="row" spacing={1}>
                        <DraftsTwoTone fontSize="small" color="success" />
                        <Typography fontSize={16} color="primary.main">
                            {clientEmail}
                        </Typography>
                    </Stack>
                )
            );
        case "phone":
            const clientPhone = row.phone;
            const hasPerm = hasPermission(auth, "Can View Company Phone");
            return (
                clientPhone &&
                hasPerm && (
                    <>
                        <Typography fontSize={16} color="primary.main">
                            {clientPhone}
                        </Typography>
                        {row.last_call_date && (
                            <Typography
                                variant="caption"
                                display="inline"
                                sx={{
                                    display: "flex",
                                    alignItems: "center",
                                }}
                            >
                                <CallMadeRounded
                                    color="success"
                                    fontSize="small"
                                    sx={{ mr: 1 }}
                                />
                                {formatDateTime(row.last_call_date)}
                            </Typography>
                        )}
                    </>
                )
            );
        default:
            return null;
    }
};

export default DetailsDataCellComponent;
