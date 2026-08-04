import { disposition, timeZone } from "@/Constant/constants";
import { hasPermission } from "@/utils/AccessManager";
import { router, usePage } from "@inertiajs/react";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Link,
    Stack,
    Typography,
} from "@mui/material";
import { confirm } from "material-ui-confirm";
import { Fragment, useEffect, useState } from "react";
import {
    AutocompleteElement,
    DatePickerElement,
    FormContainer,
    SelectElement,
    TextFieldElement,
    TimePickerElement,
} from "react-hook-form-mui";
import toast from "react-hot-toast";
import { DetailsListComponent } from "./dialogs/DetailsListComponent";
import { PhoneNumber } from "./phone-number";

export default function CallDialog({
    phone,
    phoneType,
    name,
    id,
    clientId,
    assignedUserId,
    apiDataRoute = "account.api-data1",
    submitDispositionRoute = "account.submitdisposition",
    historyRoute = "account.getcallhistory",
    dontShowNo,
}) {
    const [dispositionId, setDispositionId] = useState(null);
    const [open, setOpen] = useState(false);
    const [openDispositon, setOpenDisposition] = useState(false);
    const [show, setShow] = useState(false);
    const [activityLogId, setActivityLogId] = useState(null);
    const [detailTimeLineData, setDetailTimeLineData] = useState({});
    const [selectedItem, setSelectedItem] = useState(null); // Track the clicked item
    const [selectedItem1, setSelectedItem1] = useState(null); // Track the clicked item
    const [selectedItem2, setSelectedItem2] = useState(null); // Track the clicked item

    const {
        props: { auth },
        url,
    } = usePage();

    const canSeePhoneNumber = hasPermission(auth, "Can View Company Phone");

    const [openDetails, setOpenDetails] = useState(false);

    const defaultValues = {
        dispositionType: null,
        scheduleDate: "",
        scheduleTime: "",
        timeZone: "",
        description: "",
        flag: 0,
    };

    useEffect(() => {
        window.addEventListener("keydown", handleKeyDown);

        return () => {
            window.removeEventListener("keydown", handleKeyDown);
        };
    }, [selectedItem, selectedItem1, selectedItem2]); // Re-run useEffect if `selectedItem` changes

    const handleKeyDown = (event) => {
        // Check if F5 or Ctrl+R is pressed
        if (
            (event.key === "F5" || (event.ctrlKey && event.key === "r")) &&
            selectedItem !== null
        ) {
            // event.preventDefault(); // Prevent the default browser reload behavior
            // console.log(
            //     `Executing function for selected item: ${selectedItem}`
            // );
            myCustomFunction(selectedItem, selectedItem1, selectedItem2);
        }
    };

    const myCustomFunction = (phone, companyid, clientid) => {
        // console.log(
        //     `Custom function executed for Item:---- ${phone}, ${companyid}, ${clientid}`
        // );
        fetch(route(apiDataRoute), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                companyId: companyid,
                phone: phone,
                clientId: clientid,
            }),
        })
            .then((response) => response.json())
            .then((res) => {
                setDispositionId(res.disposition.id);
                setActivityLogId(res.activityLog.id);
            })
            .catch((error) => {
                console.error(error);
            });
    };

    const handleItemClick = (phone, companyid, clientid) => {
        setSelectedItem(phone); // Update the selected item
        setSelectedItem1(companyid);
        setSelectedItem2(clientid);
        // console.log(`Item ${clientid}, ${companyid}, ${clientid} clicked`);
    };

    const handleDetailsTimeLineOpen = () => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        // API call to get timeline data
        fetch(route(historyRoute), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                companyId: id,
                phone,
                assignedUserId,
            }),
        })
            .then((response) => response.json())
            .then((res) => {
                // Update detailTimeLineData with the response data
                setDetailTimeLineData(res);
                setOpenDetails(true);
            })
            .catch((error) => {
                console.error(error);
            });
    };
    const handelDetailsClose = () => setOpenDetails(false);

    const handleAgree = (phone) => {
        if (phone) {
            window.location.href = `tel:${phone}`;
        }

        setOpen(false);
        setOpenDisposition(true);
    };

    const handleCloseDisposition = () => setOpenDisposition(false);

    const handleSubmit = (data) => {
        data["dispositionId"] = dispositionId;
        data["activityLogId"] = activityLogId;
        data["phone"] = phone;
        data["companyId"] = id;
        data["clientId"] = clientId;
        data["dispositionType"] = data.dispositionType.id;

        const url = router.page.url;

        if (data.scheduleDate && data.scheduleTime) {
            const schedule_date = new Date(data.scheduleDate);
            const schedule_time = new Date(data.scheduleTime);

            data["scheduleDate"] = `${schedule_date.getFullYear()}-${
                schedule_date.getMonth() + 1
            }-${schedule_date.getDate()}`;
            data[
                "scheduleTime"
            ] = `${schedule_time.getHours()}:${schedule_time.getMinutes()}:${schedule_time.getSeconds()}`;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route(submitDispositionRoute), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(data),
        })
            .then((response) => response.json())
            .then((res) => {
                toast.success(res.message);

                router.get(
                    url,
                    {},
                    { preserveScroll: true, preserveState: true }
                );

                setOpenDisposition(false);
            })
            .catch((error) => {
                console.error(error);
            });
    };

    const handleOnChange = (value) => {
        if (
            value === "Follow Up" ||
            value === "Call Back" ||
            value === "Interested"
        ) {
            setShow(true);
        } else {
            setShow(false);
        }
    };

    const handleClickOpen = () => {
        handleItemClick(phone, id, clientId);
        confirm({
            title: `Call ${name}`,
            description:
                // ReactDOMServer.renderToString(
                //     <PageRefresh
                //         coname={id}
                //         clientid={clientId}
                //         phoneno={phone}
                //     />
                // ) +
                `Do you want to call this no. ${
                    hasPermission(auth, "Can View Company Phone") ? phone : name
                }?`,
            confirmationText: "Call",
            cancellationText: "Cancel",
            confirmationButtonProps: {
                color: "success",
            },
            cancellationButtonProps: {
                color: "error",
            },
        })
            .then(() => {
                handleAgree(phone);
            })
            .catch(() => {});
    };

    return (
        <Fragment>
            {phone ? (
                <>
                    <Stack direction="column" alignItems="flex-start">
                        <PhoneNumber
                            name={name}
                            phoneNumber={phone}
                            phoneType={phoneType}
                            iconClick={handleDetailsTimeLineOpen}
                            buttonClick={handleClickOpen}
                            companyId={id}
                            clientId={clientId}
                            dontShowNo={dontShowNo}
                        />
                    </Stack>
                    <Dialog
                        fullWidth
                        aria-labelledby="customized-dialog-title"
                        open={openDispositon}
                        onClose={(_, reason) =>
                            reason !== "backdropClick" &&
                            handleCloseDisposition()
                        }
                        disableEscapeKeyDown
                    >
                        <DialogTitle
                            component={Stack}
                            direction="row"
                            alignItems="center"
                            justifyContent="space-between"
                            id="customized-dialog-title"
                        >
                            <Typography
                                component={Link}
                                color="inherit"
                                underline="none"
                                href={`tel:${phone}`}
                                sx={{ ":hover": { color: "primary.main" } }}
                            >
                                {`Disposition (${
                                    canSeePhoneNumber ? phone : name
                                })`}
                            </Typography>
                            <Button
                                variant="outlined"
                                onClick={handleDetailsTimeLineOpen}
                            >
                                Details
                            </Button>
                        </DialogTitle>
                        <FormContainer
                            defaultValues={defaultValues}
                            onSuccess={handleSubmit}
                        >
                            <DialogContent dividers>
                                <Stack direction="column" spacing={1}>
                                    <AutocompleteElement
                                        name="dispositionType"
                                        label="Disposition"
                                        options={disposition}
                                        autocompleteProps={{
                                            getOptionLabel: (option) =>
                                                option.label,
                                            isOptionEqualToValue: (
                                                option,
                                                value
                                            ) => option.id === value.id,
                                            onChange: (_, value) =>
                                                handleOnChange(value.id),
                                        }}
                                    />
                                    {show && (
                                        <>
                                            <DatePickerElement
                                                name="scheduleDate"
                                                label="Schedule Date"
                                                type="date"
                                            />
                                            <TimePickerElement
                                                name="scheduleTime"
                                                label="Schedule Time"
                                            />
                                            <SelectElement
                                                label="Time Zone"
                                                name="timeZone"
                                                options={timeZone}
                                            />
                                        </>
                                    )}

                                    <TextFieldElement
                                        required
                                        multiline
                                        rows={4}
                                        name="description"
                                        label="Description"
                                    />
                                </Stack>
                            </DialogContent>
                            <DialogActions>
                                <Button type="submit">Submit</Button>
                            </DialogActions>
                        </FormContainer>
                    </Dialog>

                    <DetailsListComponent
                        open={openDetails}
                        handleClose={handelDetailsClose}
                        detailTimeLineData={detailTimeLineData}
                        auth={auth}
                    />
                </>
            ) : (
                <></>
            )}
        </Fragment>
    );
}
