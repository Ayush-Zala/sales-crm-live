import { Chip as MuiChip, Stack, styled, Typography } from "@mui/material";
import { green } from "@mui/material/colors";

import { formatDateTime } from "@/utils/date-time-formatters";
import LeadDispositionStatusComponent from "./LeadDispositionStatusComponent";

export const LeadDispositionComponent = ({ props }) => {
    return (
        props.retention_disposition && (
            <Stack direction="column" spacing={0.5} alignItems="flex-end">
                {props.retention_disposition && (
                    <Stack direction="row" spacing={0.5}>
                        {props.retention_disposition?.phone && (
                            <Typography variant="body2" color="primary.main">
                                {props.retention_disposition.phone} {" - "}
                            </Typography>
                        )}
                        {props.retention_disposition && (
                            <LeadDispositionStatusComponent
                                label={props.retention_disposition?.status}
                            />
                        )}
                    </Stack>
                )}

                <Chip
                    clickable
                    size="small"
                    label={formatDateTime(
                        props.retention_disposition?.updatedAt
                    )}
                    sx={{ borderRadius: 1 }}
                />
            </Stack>
        )
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
