import { formatDateTime } from "@/utils/date-time-formatters";
import { Chip as MuiChip, styled } from "@mui/material";
import { green } from "@mui/material/colors";
import MuiLink from "@mui/material/Link";

export default function DispositionDataCellComponent({ row, column }) {
    switch (column.id) {
        case "company_name":
            return (
                <MuiLink
                    underline="none"
                    href={route("account.view", { id: row.company_id })}
                    target="_blank"
                >
                    {row.company_name}
                </MuiLink>
            );
        case "user_name":
            return row.user_name;
        case "updated_at":
            return (
                row.updated_at && (
                    <Chip
                        clickable
                        size="small"
                        label={formatDateTime(row.updated_at)}
                        sx={{ borderRadius: 1 }}
                    />
                )
            );
        default:
            return null;
    }
}

const Chip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${green[100]};
    color: ${green[800]};
    &:hover {
        background-color: ${green[100]};
        color: ${green[800]};
    }
    &:active {
        background-color: ${green[100]};
        color: ${green[800]};
    }
    &:focus {
        background-color: ${green[100]};
        color: ${green[800]};
    }
`;
