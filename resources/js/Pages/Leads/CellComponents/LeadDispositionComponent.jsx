import { Chip as MuiChip, Stack, styled, Typography } from "@mui/material";
import { green } from "@mui/material/colors";

import { formatDateTime } from "@/utils/date-time-formatters";
import LeadDispositionStatusComponent from "./LeadDispositionStatusComponent";

export const LeadDispositionComponent = ({ props }) => {
    return (
        <Stack direction="column" spacing={0.5} alignItems="flex-end">
            <Stack direction="row" spacing={0.5}>
                {props.disposition[0]?.phone && (
                    <Typography variant="body2" color="primary.main">
                        {props.disposition[0].phone} {" - "}
                    </Typography>
                )}
                {props.disposition[0] && (
                    <LeadDispositionStatusComponent
                        label={
                            props.disposition[0]?.lead_disposition_status?.name
                        }
                    />
                )}
            </Stack>
            {props.disposition[0] && (
                <Chip
                    clickable
                    size="small"
                    label={formatDateTime(props.disposition[0]?.updated_at)}
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
