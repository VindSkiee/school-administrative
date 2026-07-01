import api from '../../api';

const extractFilenameFromDisposition = (contentDisposition) => {
    if (!contentDisposition) {
        return null;
    }

    // Prioritaskan format RFC5987: filename*=UTF-8''...
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

const sanitizeFilenamePart = (value = '') =>
    String(value)
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

const parseBlobErrorResponse = async (error) => {
    if (!(error?.response?.data instanceof Blob)) {
        return null;
    }

    try {
        const text = await error.response.data.text();
        const json = JSON.parse(text);

        return json.error || json.message || 'Gagal mengunduh PDF';
    } catch {
        return 'Gagal mengunduh PDF';
    }
};

export const triggerBlobDownload = (blob, filename = 'rapor-semester.pdf') => {
    const blobUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = blobUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();

    document.body.removeChild(link);
    window.URL.revokeObjectURL(blobUrl);
};

export const reportService = {
    /**
     * Mendapatkan statistik kehadiran berdasarkan Tahun Ajaran.
     */
    getAttendanceSummary(academicYearId) {
        return api.get('/v1/admin/reports/attendance', {
            params: { academic_year_id: academicYearId }
        });
    },

    /**
     * Mendapatkan statistik akademik berdasarkan Tahun Ajaran.
     */
    getAcademicSummary(academicYearId) {
        return api.get('/v1/admin/reports/academic', {
            params: { academic_year_id: academicYearId }
        });
    },

    /**
     * Mendapatkan daftar distribusi kesiapan rapor per siswa.
     */
    getDistributionList(academicYearId) {
        return api.get('/v1/admin/reports/distribution', {
            params: { academic_year_id: academicYearId }
        });
    },

    /**
     * Mendapatkan status kesiapan semua kelas dalam tahun ajaran.
     */
    getClassesReadiness(academicYearId) {
        return api.get(`/v1/admin/academic-years/${academicYearId}/classes-readiness`);
    },

    /**
     * Mendapatkan detail kesiapan satu kelas — per mapel, per tipe penilaian.
     */
    getClassReadinessDetail(academicYearId, classId) {
        return api.get(`/v1/admin/academic-years/${academicYearId}/classes/${classId}/readiness-detail`);
    },

    /**
     * Publish satu kelas.
     */
    publishClass(academicYearId, classId) {
        return api.patch(`/v1/admin/academic-years/${academicYearId}/classes/${classId}/publish`);
    },

    /**
     * Publish semua kelas yang siap sekaligus.
     */
    publishAllClasses(academicYearId) {
        return api.patch(`/v1/admin/academic-years/${academicYearId}/publish-all-classes`);
    },

    /**
     * Download PDF rapor siswa (WAJIB blob agar file tidak corrupt).
     */
    async downloadStudentSemesterPdf(academicYearId, studentId, studentName = 'siswa') {
        try {
            const response = await api.get(
                `/v1/admin/reports/semester/${academicYearId}/students/${studentId}/pdf`,
                {
                    responseType: 'blob'
                }
            );

            const headerFilename = extractFilenameFromDisposition(
                response.headers?.['content-disposition']
            );
            const safeStudentName = sanitizeFilenamePart(studentName) || 'siswa';
            const fallbackFilename = `rapor-${safeStudentName}-${academicYearId}.pdf`;

            triggerBlobDownload(response.data, headerFilename || fallbackFilename);
            return response;
        } catch (error) {
            const blobErrorMessage = await parseBlobErrorResponse(error);

            if (blobErrorMessage) {
                throw new Error(blobErrorMessage);
            }

            throw error;
        }
    }
};
