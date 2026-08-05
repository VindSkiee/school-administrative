import api from '../../api';

const extractFilenameFromDisposition = (contentDisposition) => {
    if (!contentDisposition) {
        return null;
    }

    const utf8FilenameMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8FilenameMatch?.[1]) {
        return decodeURIComponent(utf8FilenameMatch[1]);
    }

    const asciiFilenameMatch = contentDisposition.match(/filename="?([^\";]+)"?/i);
    if (asciiFilenameMatch?.[1]) {
        return asciiFilenameMatch[1];
    }

    return null;
};

const triggerBlobDownload = (blob, filename = 'rapor-semester.pdf') => {
    const blobUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = blobUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();

    document.body.removeChild(link);
    window.URL.revokeObjectURL(blobUrl);
};

export const studentReportService = {
    // List academic years the student has classes in
    getAcademicYears() {
        return api.get('/v1/student/reports/academic-years');
    },

    // Ambil data agregat nilai harian + detail tugas (Untuk tabel & dropdown)
    getGradesAggregate(academicYearId) {
        return api.get('/v1/student/grades/aggregate', {
            params: academicYearId ? { academic_year_id: academicYearId } : {},
        });
    },

    // Ambil metadata status publikasi rapor (lightweight — never returns 403)
    getSemesterReportStatus(academicYearId) {
        return api.get('/v1/student/reports/report-status', {
            params: academicYearId ? { academic_year_id: academicYearId } : {},
        });
    },

    /**
     * Download PDF rapor semester secara synchronous (blob response).
     */
    async downloadSemesterPdf(academicYearId) {
        try {
            const response = await api.get('/v1/student/reports/semester/pdf', {
                params: academicYearId ? { academic_year_id: academicYearId } : {},
                responseType: 'blob',
            });

            const headerFilename = extractFilenameFromDisposition(
                response.headers?.['content-disposition'],
            );

            triggerBlobDownload(response.data, headerFilename || 'rapor-semester.pdf');
            return response;
        } catch (error) {
            if (error?.response?.data instanceof Blob) {
                try {
                    const text = await error.response.data.text();
                    const json = JSON.parse(text);
                    throw new Error(json.error || json.message || 'Gagal mengunduh PDF');
                } catch {
                    throw new Error('Gagal mengunduh PDF');
                }
            }

            throw error;
        }
    },
};
