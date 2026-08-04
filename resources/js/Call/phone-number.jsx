import { usePage } from "@inertiajs/react";
import { HistoryIcon } from "lucide-react";

import { hasPermission, hasRole } from "@/utils/AccessManager";
import { Typography } from "@mui/material";
import Button from "@mui/material/Button";
import IconButton from "@mui/material/IconButton";
import Stack from "@mui/material/Stack";
import Tooltip from "@mui/material/Tooltip";
import AddRemarkDialog from "./dialogs/AddRemarkDialog";

export const PhoneNumber = ({
    name,
    phoneNumber,
    phoneType,
    iconClick,
    buttonClick,
    companyId,
    clientId,
    dontShowNo,
    ...rest
}) => {
    const {
        props: { auth },
        url,
    } = usePage();

    const isDEM = hasRole(auth, ["Data Entry Manager", "Data Entry"]);

    let addremarkUrl = url === "/lead" && "lead.addremark";

    return !isDEM ? (
        <Stack direction="row" alignItems="center">
            <Tooltip arrow placement="left" title="Calling history">
                <IconButton color="success" size="small" onClick={iconClick}>
                    <HistoryIcon size={18} />
                </IconButton>
            </Tooltip>
            <AddRemarkDialog
                companyId={companyId}
                clientId={clientId}
                phone={phoneNumber}
                addremarkUrl={addremarkUrl}
            />
            <Button
                variant="text"
                size="small"
                onClick={buttonClick}
                color={dontShowNo ? "error" : "primary"}
                sx={{
                    textDecoration: dontShowNo ? "line-through" : "none",
                    ":hover": dontShowNo
                        ? { textDecoration: "line-through" }
                        : {},
                }}
            >
                {hasPermission(auth, "Can View Company Phone")
                    ? `${phoneNumber} (${phoneType})`
                    : name}
            </Button>
        </Stack>
    ) : (
        <Typography
            fontSize="inherit"
            color={dontShowNo ? "error.main" : "primary.main"}
            sx={{
                textDecoration: dontShowNo ? "line-through" : "none",
            }}
        >
            {`${phoneNumber} (${phoneType})`}
        </Typography>
    );
};
