import { format, formatDistanceToNow, getTime, parseISO } from "date-fns";
import { utcToZonedTime } from "date-fns-tz";

export function formatDateTime(dateTime, formatString) {
    return dateTime
        ? format(
              parseISO(dateTime),
              formatString || "do, MMMM yyyy, hh:mm:ss a"
          )
        : null;
}

export function formatDate(date, formatString) {
    return date
        ? format(parseISO(date), formatString || "do, MMMM yyyy")
        : null;
}

export function formatTime(time, formatString) {
    return time ? format(parseISO(time), formatString) : null;
}

export function timeAgo(dateTime, addSuffix = true) {
    return dateTime
        ? formatDistanceToNow(parseISO(dateTime), {
              includeSeconds: true,
              addSuffix: addSuffix,
          })
        : null;
}

export function convertToIST(dateString, formatString) {
    const zonedDate = utcToZonedTime(dateString, "Asia/Kolkata");

    return format(
        zonedDate,
        formatString ? formatString : "yyyy-MM-dd HH:mm:ss"
    );
}

export function isBetween(inputDate, startDate, endDate) {
    const date = new Date(inputDate);

    const results =
        new Date(date.toDateString()) >= new Date(startDate.toDateString()) &&
        new Date(date.toDateString()) <= new Date(endDate.toDateString());

    return results;
}

export function isAfter(startDate, endDate) {
    const results =
        startDate && endDate
            ? new Date(startDate).getTime() > new Date(endDate).getTime()
            : false;

    return results;
}

export function fDate(date, newFormat) {
    const fm = newFormat || "dd MMM yyyy";

    return date ? format(new Date(date), fm) : "";
}

export function fTime(date, newFormat) {
    const fm = newFormat || "p";

    return date ? format(new Date(date), fm) : "";
}

export function fDateTime(date, newFormat) {
    const fm = newFormat || "dd MMM yyyy p";

    return date ? format(new Date(date), fm) : "";
}

export function fTimestamp(date) {
    return date ? getTime(new Date(date)) : "";
}

export function fToNow(date) {
    return date
        ? formatDistanceToNow(new Date(date), {
              addSuffix: true,
          })
        : "";
}
