import { yupResolver } from "@hookform/resolvers/yup";
import PropTypes from "prop-types";
import { Controller, FormProvider, useForm } from "react-hook-form";
import * as Yup from "yup";

import LoadingButton from "@mui/lab/LoadingButton";
import Box from "@mui/material/Box";
import Button from "@mui/material/Button";
import DialogActions from "@mui/material/DialogActions";
import IconButton from "@mui/material/IconButton";
import Stack from "@mui/material/Stack";
import Tooltip from "@mui/material/Tooltip";
import { MobileDateTimePicker } from "@mui/x-date-pickers/MobileDateTimePicker";
import debounce from "lodash.debounce";

// import { createEvent, updateEvent, deleteEvent } from 'src/api/calendar';

import { timeZone } from "@/Constant/constants";
import useEventsStore from "@/store/events-store";
import { isAfter } from "@/utils/date-time-formatters";
import { DeleteRounded } from "@mui/icons-material";
import {
    Autocomplete,
    Checkbox,
    CircularProgress,
    DialogContent,
    FormControlLabel,
    MenuItem,
    TextField,
} from "@mui/material";
import { format } from "date-fns";
import {
    createEvent,
    createZoomEvent,
    deleteEvent,
    deleteZoomEvent,
    updateEvent,
    updateZoomEvent,
} from "./api/calendar";
import { ColorPicker } from "./color-utils";
import { RHFSwitch, RHFTextField } from "./hook-form";
import toast from "react-hot-toast";
import { hasPermission } from "@/utils/AccessManager";
import { usePage } from "@inertiajs/react";
import { useState } from "react";

// ----------------------------------------------------------------------

export default function CalendarForm({ currentEvent, onClose }) {
    const { auth } = usePage().props;
    const hasDeletePermission = hasPermission(auth, "Can Delete Event");

    const EventSchema = Yup.object().shape({
        title: Yup.string().max(255).required("Title is required"),
        description: Yup.string().max(
            5000,
            "Description must be at most 5000 characters"
        ),
        company_id: Yup.object().nullable(),
        allDay: Yup.boolean(),
        start: Yup.mixed(),
        end: Yup.mixed(),
        timezone: Yup.string(),
        zoomMeeting: Yup.boolean().nullable(),
        zoomMeetingId: Yup.string(),
    });

    const methods = useForm({
        resolver: yupResolver(EventSchema),
        defaultValues: {
            title: currentEvent?.title || "",
            description: currentEvent?.description || "",
            company_id:
                {
                    id: currentEvent?.companyId,
                    name: currentEvent?.companyName,
                } || null,
            allDay: currentEvent?.allDay || false,
            start: currentEvent?.start || null,
            end: currentEvent?.end || null,
            timezone: currentEvent?.timezone || timeZone[0].id,
            zoomMeeting: currentEvent?.zoomMeeting || false,
            zoomMeetingId: currentEvent?.zoomMeetingId || "",
        },
    });

    const { addEventToStore, updateEventToStore, deleteEventFromStore } =
        useEventsStore();

    const {
        reset,
        watch,
        control,
        handleSubmit,
        formState: { isSubmitting },
    } = methods;

    const values = watch();

    const dateError = isAfter(values.start, values.end);

    const [companies, setCompanies] = useState([]);
    const [loading, setLoading] = useState(false);

    // Function to fetch companies from API
    const fetchCompanies = async (value) => {
        try {
            setLoading(true);
            await fetch(route("calendar.searchcompanies", { company: value }))
                .then((response) => response.json())
                .then((data) => {
                    setCompanies(data);
                })
                .catch((error) => {
                    console.error("Error fetching companies:", error);
                });
        } catch (error) {
            console.error("Error fetching companies:", error);
        } finally {
            setLoading(false);
        }
    };

    // Debounced API call
    const debouncedFetch = debounce((value) => {
        if (value) fetchCompanies(value);
    }, 300);

    const onSubmit = (data) => {
        const formattedData = {
            ...data,
            company_id: data.company_id?.id || null,
            start: data.start
                ? format(new Date(data.start), "yyyy-MM-dd HH:mm:ss")
                : null,
            end: data.end
                ? format(new Date(data.end), "yyyy-MM-dd HH:mm:ss")
                : null,
        };

        try {
            if (!dateError) {
                if (currentEvent?.id) {
                    if (data.zoomMeeting === true) {
                        updateZoomEvent(
                            { ...formattedData, id: currentEvent.id },
                            updateEventToStore
                        ).then(() => {
                            onClose();
                            reset();
                        });

                        return;
                    }

                    updateEvent(
                        { ...formattedData, id: currentEvent.id },
                        updateEventToStore
                    ).then(() => {
                        onClose();
                        reset();
                    });
                } else {
                    if (data.zoomMeeting === true) {
                        createZoomEvent(formattedData, addEventToStore).then(
                            () => {
                                onClose();
                                reset();
                            }
                        );
                        return;
                    }
                    createEvent(formattedData, addEventToStore).then(() => {
                        onClose();
                        reset();
                    });
                }
            }
        } catch (error) {
            console.error(error);
        }
    };

    const handleDeleteEvent = () => {
        // console.log(currentEvent);
        try {
            if (currentEvent.zoomMeeting === 1) {
                deleteZoomEvent(
                    currentEvent.id,
                    currentEvent.zoomMeetingId,
                    deleteEventFromStore
                ).then(() => {
                    onClose();
                    reset();
                });
                return;
            }

            deleteEvent(currentEvent.id, deleteEventFromStore).then(() => {
                onClose();
                reset();
            });
        } catch (error) {
            console.error(error);
        }
    };

    return (
        <FormProvider {...methods}>
            <form onSubmit={handleSubmit(onSubmit)}>
                <DialogContent dividers>
                    <Stack spacing={3}>
                        <RHFTextField name="title" label="Title" type="text" />

                        <RHFTextField
                            name="description"
                            label="Description"
                            type="text"
                            multiline
                            rows={3}
                        />

                        <RHFSwitch name="allDay" label="All day" />

                        {/* Autocomplete for Company */}
                        <Controller
                            name="company_id"
                            control={control}
                            render={({ field }) => (
                                <Autocomplete
                                    {...field}
                                    options={companies || []} // List of company objects
                                    value={field.value || null} // The selected company object or null
                                    getOptionLabel={(option) =>
                                        option.name || ""
                                    } // Display the company name
                                    onInputChange={(event, value) =>
                                        debouncedFetch(value)
                                    } // Fetch companies based on input
                                    onChange={(event, newValue) => {
                                        field.onChange(newValue || null); // Store the selected company object
                                    }}
                                    isOptionEqualToValue={(option, value) =>
                                        option.id === value?.id
                                    }
                                    loading={loading}
                                    renderInput={(params) => (
                                        <TextField
                                            {...params}
                                            label="Select Company"
                                            placeholder="Type to search..."
                                            InputProps={{
                                                ...params.InputProps,
                                                endAdornment: (
                                                    <>
                                                        {loading ? (
                                                            <CircularProgress
                                                                size={20}
                                                            />
                                                        ) : null}
                                                        {
                                                            params.InputProps
                                                                .endAdornment
                                                        }
                                                    </>
                                                ),
                                            }}
                                        />
                                    )}
                                />
                            )}
                        />

                        <Controller
                            name="start"
                            control={control}
                            render={({ field }) => (
                                <MobileDateTimePicker
                                    {...field}
                                    value={
                                        field.value
                                            ? new Date(field.value)
                                            : null
                                    }
                                    onChange={(newValue) => {
                                        if (newValue) {
                                            field.onChange(newValue);
                                        }
                                    }}
                                    format="dd MMM yyyy hh:mm a"
                                    label="Start date"
                                    slotProps={{
                                        textField: {
                                            fullWidth: true,
                                        },
                                    }}
                                    // renderInput={(params) => <TextField {...params} />}
                                />
                            )}
                        />

                        <Controller
                            name="end"
                            control={control}
                            render={({ field }) => (
                                <MobileDateTimePicker
                                    {...field}
                                    value={
                                        field.value
                                            ? new Date(field.value)
                                            : null
                                    }
                                    onChange={(newValue) => {
                                        if (newValue) {
                                            field.onChange(newValue);
                                        }
                                    }}
                                    format="dd MMM yyyy hh:mm a"
                                    label="End date"
                                    slotProps={{
                                        textField: {
                                            fullWidth: true,
                                            error: dateError,
                                            helperText:
                                                dateError &&
                                                "End date must be later than start date",
                                        },
                                    }}
                                    // renderInput={(params) => <TextField {...params} />}
                                />
                            )}
                        />

                        {/* Select element for timezone */}
                        <Controller
                            name="timezone"
                            control={control}
                            render={({ field }) => (
                                <TextField
                                    {...field}
                                    select
                                    fullWidth
                                    label="Timezone"
                                    variant="outlined"
                                >
                                    {timeZone.map((timezone) => (
                                        <MenuItem
                                            key={timezone.id}
                                            value={timezone.id}
                                        >
                                            {timezone.label}
                                        </MenuItem>
                                    ))}
                                </TextField>
                            )}
                        />

                        {/* Zoom meeting */}
                        <Controller
                            name="zoomMeeting"
                            control={control}
                            defaultValue={Boolean(currentEvent?.zoomMeeting)}
                            render={({ field }) => (
                                <FormControlLabel
                                    control={
                                        <Checkbox
                                            {...field}
                                            checked={field.value}
                                        />
                                    }
                                    label="Zoom Meeting"
                                />
                            )}
                        />

                        {/* <Controller
                            name="color"
                            control={control}
                            render={({ field }) => (
                                <ColorPicker
                                    selected={field.value}
                                    onSelectColor={(color) =>
                                        field.onChange(color)
                                    }
                                    colors={colorOptions}
                                />
                            )}
                        /> */}
                    </Stack>
                </DialogContent>
                <DialogActions>
                    {!!currentEvent?.id && hasDeletePermission && (
                        <Tooltip title="Delete Event">
                            <IconButton onClick={handleDeleteEvent}>
                                <DeleteRounded />
                            </IconButton>
                        </Tooltip>
                    )}

                    <Box sx={{ flexGrow: 1 }} />

                    <Button
                        variant="outlined"
                        color="inherit"
                        onClick={onClose}
                    >
                        Cancel
                    </Button>

                    <LoadingButton
                        type="submit"
                        variant="contained"
                        loading={isSubmitting}
                        disabled={dateError}
                    >
                        Save Changes
                    </LoadingButton>
                </DialogActions>
            </form>
        </FormProvider>
    );
}

CalendarForm.propTypes = {
    colorOptions: PropTypes.arrayOf(PropTypes.string),
    currentEvent: PropTypes.object,
    onClose: PropTypes.func,
};
