import { getRoleBgColor, getRoleColor } from "@/Constant/constants";
import { Chip as MuiChip, styled } from "@mui/material";
import { orange, pink } from "@mui/material/colors";
import IsActiveSwitch from "./cellComponents/IsActiveSwitch";
import UserActions from "./cellComponents/UserActions";
import UserLinkComponent from "./cellComponents/UserLinkComponent";

export default function UserTableCellComponent({ row, column }) {
    switch (column.id) {
        case "name":
            return <UserLinkComponent userdata={row} />;
        case "role":
            return row.role ? (
                <Chip
                    clickable
                    size="small"
                    label={row.role}
                    sx={{ borderRadius: 1 }}
                />
            ) : null;
        case "reporting_authority_name":
            return row.reporting_authority_name ? (
                <SingleColorChip
                    clickable
                    size="small"
                    label={row.reporting_authority_name}
                    sx={{ borderRadius: 1 }}
                    bgColor={pink[100]}
                    textColor={pink[900]}
                />
            ) : null;
        case "assigned_accounts":
            return row.assigned_accounts >= 0 ? (
                <SingleColorChip
                    clickable
                    size="small"
                    label={row.assigned_accounts}
                    sx={{ borderRadius: 1 }}
                    bgColor={orange[100]}
                    textColor={orange[900]}
                />
            ) : null;
        case "is_active":
            return row.role !== "Admin" ? <IsActiveSwitch user={row} /> : null;
        case "actions":
            return <UserActions user={row} />;
        default:
            return row[column.id];
    }
}

const Chip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${({ label }) => getRoleBgColor(label)};
    color: ${({ label }) => getRoleColor(label)};
    &:hover {
        background-color: ${({ label }) => getRoleBgColor(label)};
        color: ${({ label }) => getRoleColor(label)};
    }
    &:active {
        background-color: ${({ label }) => getRoleBgColor(label)};
        color: ${({ label }) => getRoleColor(label)};
    }
    &:focus {
        background-color: ${({ label }) => getRoleBgColor(label)};
        color: ${({ label }) => getRoleColor(label)};
    }
`;

const SingleColorChip = styled(MuiChip)`
    font-weight: 600;
    background-color: ${({ bgColor }) => bgColor};
    color: ${({ textColor }) => textColor};
    &:hover {
        background-color: ${({ bgColor }) => bgColor};
        color: ${({ textColor }) => textColor};
    }
`;
