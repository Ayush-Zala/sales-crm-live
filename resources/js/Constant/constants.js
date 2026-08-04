import {
    blueGrey,
    cyan,
    deepOrange,
    green,
    indigo,
    orange,
    pink,
    purple,
    red,
    teal,
    yellow,
} from "@mui/material/colors";

export const disposition = [
    { id: "Already Client", label: "Already Client" },
    { id: "Answering Machine", label: "Answering Machine" },
    { id: "Authority Not Available", label: "Authority Not Available" },
    { id: "Call Back", label: "Call Back" },
    { id: "Cancel", label: "Cancel" },
    { id: "Disconnected Number", label: "Disconnected Number" },
    { id: "Do Not Call", label: "Do Not Call" },
    { id: "Doesn't Qualify", label: "Doesn't Qualify" },
    { id: "Follow Up", label: "Follow Up" },
    { id: "Hang Up", label: "Hang Up" },
    { id: "In House", label: "In House" },
    { id: "Not Interested", label: "Not Interested" },
    { id: "Number Not In Service", label: "Number Not in service" },
    { id: "Sale", label: "Sale" },
    { id: "No Answer", label: "No Answer" },
    { id: "Wrong Number", label: "Wrong Number" },
    { id: "Interested", label: "Interested" },
    { id: "Busy Number", label: "Busy Number" },
];

export const types = [
    { id: "primary", label: "Primary" },
    { id: "secondary", label: "Secondary" },
    { id: "phone", label: "Phone" },
    { id: "landline", label: "Landline" },
    { id: "work", label: "Work" },
    { id: "mobile", label: "Mobile" },
];

export const timeZone = [
    { id: "MST", label: "MST" },
    { id: "EST", label: "EST" },
    { id: "PST", label: "PST" },
    { id: "CST", label: "CST" },
    { id: "AST", label: "AST" },
    { id: "HST", label: "HST" },
];

export const vendorTypes = [
    { id: "fixed", label: "Fixed" },
    { id: "retail", label: "Retail" },
];

const dispositionStatusColors = {
    "Already Client": { color: green[800], bgColor: green[100] },
    "Answering Machine": { color: pink[800], bgColor: pink[100] },
    "Authority Not Available": { color: purple[800], bgColor: purple[100] },
    "Call Back": { color: orange[800], bgColor: orange[100] },
    Cancel: { color: red[800], bgColor: red[100] },
    "Closed Window": { color: pink[800], bgColor: pink[100] },
    "Disconnected Number": { color: deepOrange[800], bgColor: deepOrange[100] },
    "Do Not Call": { color: indigo[800], bgColor: indigo[100] },
    "Doesn`t Qualify": { color: cyan[800], bgColor: cyan[100] },
    "Follow Up": { color: teal[800], bgColor: teal[100] },
    "Hang Up": { color: pink[800], bgColor: pink[100] },
    "In House": { color: green[800], bgColor: green[100] },
    "Not Interested": { color: deepOrange[800], bgColor: deepOrange[100] },
    "Number Not In Service": { color: blueGrey[800], bgColor: blueGrey[100] },
    Sale: { color: green[800], bgColor: green[100] },
    "No Answer": { color: pink[800], bgColor: pink[100] },
    "Window Refresh": { color: yellow[800], bgColor: yellow[100] },
    "Wrong Number": { color: deepOrange[800], bgColor: deepOrange[100] },
    "Doesn't Qualify": { color: yellow[800], bgColor: yellow[100] },
    Interested: { color: indigo[800], bgColor: indigo[100] },
};

export function getColor(status) {
    return dispositionStatusColors[status]?.color || "";
}

export function getBgColor(status) {
    return dispositionStatusColors[status]?.bgColor || "";
}

const roleColors = {
    Admin: { color: green[800], bgColor: green[100] },
    "Business Development Manager": { color: pink[800], bgColor: pink[100] },
    "Business Development Team Lead": { color: teal[800], bgColor: teal[100] },
    "Sales Executives": { color: purple[800], bgColor: purple[100] },
    "Super Admin": { color: orange[800], bgColor: orange[100] },
    norele: { color: red[800], bgColor: red[100] },
    "Data Entry": { color: pink[800], bgColor: pink[100] },
    "Data Entry Manager": { color: deepOrange[800], bgColor: deepOrange[100] },
    sub: { color: indigo[800], bgColor: indigo[100] },
};

export function getRoleColor(role) {
    return roleColors[role]?.color || "";
}

export function getRoleBgColor(role) {
    return roleColors[role]?.bgColor || "";
}

export const targetAcheivedColors = {
    completed: { color: green[800], bgColor: green[100] },
    eightyPercent: { color: orange[800], bgColor: orange[100] },
    fiftyPercent: { color: deepOrange[800], bgColor: deepOrange[100] },
    belowFifty: { color: yellow[800], bgColor: yellow[100] },
    zeroPercent: { color: red[800], bgColor: red[100] },
};

export function getTargetAcheivedColor(percentage) {
    const target =
        percentage >= 80 && percentage <= 100
            ? "completed"
            : percentage >= 60 && percentage < 80
            ? "eightyPercent"
            : percentage >= 50 && percentage < 60
            ? "fiftyPercent"
            : percentage > 0 && percentage < 50
            ? "belowFifty"
            : "zeroPercent";

    return targetAcheivedColors[target]?.color || "";
}

export function getTargetAcheivedBgColor(percentage) {
    const target =
        percentage >= 80 && percentage <= 100
            ? "completed"
            : percentage >= 60 && percentage < 80
            ? "eightyPercent"
            : percentage >= 50 && percentage < 60
            ? "fiftyPercent"
            : percentage > 0 && percentage < 50
            ? "belowFifty"
            : "zeroPercent";

    return targetAcheivedColors[target]?.bgColor || "";
}
