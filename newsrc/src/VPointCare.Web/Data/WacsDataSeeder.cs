using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data.Entities;

namespace VPointCare.Web.Data;

public class WacsDataSeeder(VPointCareDbContext dbContext, IConfiguration configuration, ILogger<WacsDataSeeder> logger)
{
    public async Task SeedAsync(CancellationToken cancellationToken = default)
    {
        var now = DateTime.UtcNow;

        await SeedRolesAsync(now, cancellationToken);
        await SeedChatStatusesAsync(now, cancellationToken);
        await SeedTicketStatusesAsync(now, cancellationToken);
        await SeedTicketPrioritiesAsync(now, cancellationToken);
        await SeedTicketCategoriesAsync(now, cancellationToken);
        await SeedAiSettingsAsync(now, cancellationToken);
        await SeedAdminUserAsync(now, cancellationToken);

        await dbContext.SaveChangesAsync(cancellationToken);
        logger.LogInformation("Seed data WACS selesai dijalankan.");
    }

    private async Task SeedRolesAsync(DateTime now, CancellationToken cancellationToken)
    {
        await UpsertRoleAsync("ADMIN", "Admin", "Akses penuh aplikasi", now, cancellationToken);
        await UpsertRoleAsync("SUPERVISOR_CS", "Supervisor CS", "Monitoring dan pengaturan customer service", now, cancellationToken);
        await UpsertRoleAsync("CS", "Customer Service", "Menangani chat dan membuat ticket", now, cancellationToken);
        await UpsertRoleAsync("DEVELOPER", "Developer", "Menangani ticket teknis", now, cancellationToken);
        await UpsertRoleAsync("VIEWER", "Viewer", "Melihat dashboard dan laporan", now, cancellationToken);
    }

    private async Task UpsertRoleAsync(string code, string name, string description, DateTime now, CancellationToken cancellationToken)
    {
        var row = await dbContext.Perans.FirstOrDefaultAsync(x => x.KodePeran == code, cancellationToken);
        if (row is null)
        {
            dbContext.Perans.Add(new MPeran
            {
                Id = Guid.NewGuid(),
                KodePeran = code,
                NamaPeran = name,
                Keterangan = description,
                NonAktif = false,
                TglBuat = now
            });
            return;
        }

        row.NamaPeran = name;
        row.Keterangan = description;
        row.NonAktif = false;
        row.TglEdit = now;
    }

    private async Task SeedChatStatusesAsync(DateTime now, CancellationToken cancellationToken)
    {
        await UpsertChatStatusAsync("BARU", "Baru", 10, "info", now, cancellationToken);
        await UpsertChatStatusAsync("MENUNGGU_CS", "Menunggu CS", 20, "warning", now, cancellationToken);
        await UpsertChatStatusAsync("DALAM_PROSES", "Dalam Proses", 30, "primary", now, cancellationToken);
        await UpsertChatStatusAsync("MENUNGGU_CUSTOMER", "Menunggu Customer", 40, "gray", now, cancellationToken);
        await UpsertChatStatusAsync("SELESAI", "Selesai", 50, "success", now, cancellationToken);
        await UpsertChatStatusAsync("DITUTUP", "Ditutup", 60, "gray", now, cancellationToken);
    }

    private async Task UpsertChatStatusAsync(string code, string name, int order, string color, DateTime now, CancellationToken cancellationToken)
    {
        var row = await dbContext.MStatusChatSet.FirstOrDefaultAsync(x => x.KodeStatusChat == code, cancellationToken);
        if (row is null)
        {
            dbContext.MStatusChatSet.Add(new MStatusChat
            {
                Id = Guid.NewGuid(),
                KodeStatusChat = code,
                NamaStatusChat = name,
                Urutan = order,
                Warna = color,
                NonAktif = false,
                TglBuat = now
            });
            return;
        }

        row.NamaStatusChat = name;
        row.Urutan = order;
        row.Warna = color;
        row.NonAktif = false;
        row.TglEdit = now;
    }

    private async Task SeedTicketStatusesAsync(DateTime now, CancellationToken cancellationToken)
    {
        await UpsertTicketStatusAsync("DRAFT", "Draft", 10, false, "gray", now, cancellationToken);
        await UpsertTicketStatusAsync("BARU", "Baru", 20, false, "info", now, cancellationToken);
        await UpsertTicketStatusAsync("DIANALISA_CS", "Dianalisa CS", 30, false, "warning", now, cancellationToken);
        await UpsertTicketStatusAsync("BUTUH_DATA_CUSTOMER", "Butuh Data Customer", 40, false, "warning", now, cancellationToken);
        await UpsertTicketStatusAsync("DITERUSKAN_DEVELOPER", "Diteruskan ke Developer", 50, false, "primary", now, cancellationToken);
        await UpsertTicketStatusAsync("DALAM_PENGERJAAN", "Dalam Pengerjaan", 60, false, "primary", now, cancellationToken);
        await UpsertTicketStatusAsync("MENUNGGU_DEPLOY", "Menunggu Deploy", 70, false, "warning", now, cancellationToken);
        await UpsertTicketStatusAsync("SELESAI", "Selesai", 80, true, "success", now, cancellationToken);
        await UpsertTicketStatusAsync("DITUTUP", "Ditutup", 90, true, "gray", now, cancellationToken);
        await UpsertTicketStatusAsync("DIBATALKAN", "Dibatalkan", 100, true, "danger", now, cancellationToken);
    }

    private async Task UpsertTicketStatusAsync(string code, string name, int order, bool final, string color, DateTime now, CancellationToken cancellationToken)
    {
        var row = await dbContext.MStatusTicketSet.FirstOrDefaultAsync(x => x.KodeStatusTicket == code, cancellationToken);
        if (row is null)
        {
            dbContext.MStatusTicketSet.Add(new MStatusTicket
            {
                Id = Guid.NewGuid(),
                KodeStatusTicket = code,
                NamaStatusTicket = name,
                Urutan = order,
                StatusFinal = final,
                Warna = color,
                NonAktif = false,
                TglBuat = now
            });
            return;
        }

        row.NamaStatusTicket = name;
        row.Urutan = order;
        row.StatusFinal = final;
        row.Warna = color;
        row.NonAktif = false;
        row.TglEdit = now;
    }

    private async Task SeedTicketPrioritiesAsync(DateTime now, CancellationToken cancellationToken)
    {
        await UpsertTicketPriorityAsync("RENDAH", "Rendah", 10, 4320, "gray", now, cancellationToken);
        await UpsertTicketPriorityAsync("NORMAL", "Normal", 20, 1440, "info", now, cancellationToken);
        await UpsertTicketPriorityAsync("TINGGI", "Tinggi", 30, 480, "warning", now, cancellationToken);
        await UpsertTicketPriorityAsync("KRITIS", "Kritis", 40, 120, "danger", now, cancellationToken);
    }

    private async Task UpsertTicketPriorityAsync(string code, string name, int order, int slaMinutes, string color, DateTime now, CancellationToken cancellationToken)
    {
        var row = await dbContext.MPrioritasTicketSet.FirstOrDefaultAsync(x => x.KodePrioritas == code, cancellationToken);
        if (row is null)
        {
            dbContext.MPrioritasTicketSet.Add(new MPrioritasTicket
            {
                Id = Guid.NewGuid(),
                KodePrioritas = code,
                NamaPrioritas = name,
                Urutan = order,
                BatasSlaMenit = slaMinutes,
                Warna = color,
                NonAktif = false,
                TglBuat = now
            });
            return;
        }

        row.NamaPrioritas = name;
        row.Urutan = order;
        row.BatasSlaMenit = slaMinutes;
        row.Warna = color;
        row.NonAktif = false;
        row.TglEdit = now;
    }

    private async Task SeedTicketCategoriesAsync(DateTime now, CancellationToken cancellationToken)
    {
        await UpsertTicketCategoryAsync("BUG", "Bug Aplikasi", "Masalah error atau bug aplikasi", now, cancellationToken);
        await UpsertTicketCategoryAsync("DATA", "Masalah Data", "Masalah data master atau transaksi", now, cancellationToken);
        await UpsertTicketCategoryAsync("AKSES", "Masalah Akses", "Login, role, permission, atau akses menu", now, cancellationToken);
        await UpsertTicketCategoryAsync("REQUEST", "Permintaan Fitur", "Permintaan fitur baru atau perubahan fitur", now, cancellationToken);
        await UpsertTicketCategoryAsync("KONSULTASI", "Konsultasi", "Pertanyaan penggunaan aplikasi", now, cancellationToken);
    }

    private async Task UpsertTicketCategoryAsync(string code, string name, string description, DateTime now, CancellationToken cancellationToken)
    {
        var row = await dbContext.MKategoriTicketSet.FirstOrDefaultAsync(x => x.KodeKategori == code, cancellationToken);
        if (row is null)
        {
            dbContext.MKategoriTicketSet.Add(new MKategoriTicket
            {
                Id = Guid.NewGuid(),
                KodeKategori = code,
                NamaKategori = name,
                Keterangan = description,
                NonAktif = false,
                TglBuat = now
            });
            return;
        }

        row.NamaKategori = name;
        row.Keterangan = description;
        row.NonAktif = false;
        row.TglEdit = now;
    }

    private async Task SeedAiSettingsAsync(DateTime now, CancellationToken cancellationToken)
    {
        var row = await dbContext.MPengaturanAiSet.FirstOrDefaultAsync(x => x.KodePengaturan == "DEFAULT", cancellationToken);
        if (row is null)
        {
            dbContext.MPengaturanAiSet.Add(new MPengaturanAi
            {
                Id = Guid.NewGuid(),
                KodePengaturan = "DEFAULT",
                NamaPengaturan = "Pengaturan Default AI Agent",
                AutoReplyAktif = false,
                AutoReplyDiluarJamKerja = true,
                AutoReplyHariLibur = true,
                AutoReplyJamKerjaSapaan = true,
                AutoReplyJamKerjaBerlanjut = false,
                JamKerjaMulai = new TimeSpan(8, 0, 0),
                JamKerjaSelesai = new TimeSpan(17, 0, 0),
                HariKerja = "1,2,3,4,5",
                ZonaWaktu = "Asia/Jakarta",
                ProviderAi = "OpenAI",
                ModelAi = "gpt-5",
                BaseUrl = "https://api.openai.com/v1/responses",
                PromptSistem = "Anda adalah AI Agent customer service VPoint Care. Jawab dalam Bahasa Indonesia yang sopan, singkat, jelas, dan jangan membuat janji teknis yang belum dipastikan. Jika masalah perlu ditangani manusia, arahkan bahwa tim customer service akan menindaklanjuti.",
                TemplateDiluarJamKerja = "Terima kasih sudah menghubungi VPoint Care. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.",
                TemplateHariLibur = "Terima kasih sudah menghubungi VPoint Care. Hari ini kami sedang libur ({nama_hari_libur}). Pesan Bapak/Ibu tetap kami terima dan akan kami teruskan ke tim customer service. Silakan sampaikan detail kendalanya agar tim kami bisa menindaklanjuti pada hari kerja berikutnya, {tanggal_masuk_kerja}. Mohon maaf atas ketidaknyamanannya.",
                TemplateJamKerjaSapaan = "Halo, terima kasih sudah menghubungi VPoint Care. Saya bantu catat terlebih dahulu ya. Silakan jelaskan kendala yang sedang dialami, nanti tim customer service kami akan melanjutkan penanganannya.",
                TemplateFallback = "Terima kasih informasinya. Pesan sudah kami terima dan akan kami teruskan ke tim terkait untuk ditindaklanjuti.",
                NotifikasiChatBelumTerbalasAktif = true,
                MenitTungguNotifikasi = 10,
                JedaNotifikasiMenit = 30,
                KodePeranPenerimaNotifikasi = "ADMIN,SUPERVISOR_CS,CS",
                TemplateNotifikasiChatBelumTerbalas = "Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek VPoint Care: {url_admin}",
                BatasRiwayatPesan = 8,
                KirimKeWaha = false,
                ModeKirim = "DraftLokal",
                NonAktif = false,
                TglBuat = now
            });
            return;
        }

        row.NonAktif = false;
        row.TglEdit = now;
    }

    private async Task SeedAdminUserAsync(DateTime now, CancellationToken cancellationToken)
    {
        var email = configuration["SeedData:Admin:Email"] ?? "mrthx.89@gmail.com";
        var name = configuration["SeedData:Admin:Name"] ?? "Admin VPoint Care";
        var password = configuration["SeedData:Admin:Password"] ?? "Ell1t3s3rv";
        var passwordHash = BCrypt.Net.BCrypt.HashPassword(password);

        var oldLocalUser = await dbContext.Users.FirstOrDefaultAsync(x => x.Email == "admin@vpointcare.local", cancellationToken);
        if (oldLocalUser is not null)
        {
            dbContext.Users.Remove(oldLocalUser);
        }

        var user = await dbContext.Users.FirstOrDefaultAsync(x => x.Email == email, cancellationToken);
        if (user is null)
        {
            user = new MUser
            {
                Name = name,
                Email = email,
                Password = passwordHash,
                Status = "approved",
                ApprovedAt = now,
                BlockedAt = null,
                CreatedAt = now,
                UpdatedAt = now
            };
            dbContext.Users.Add(user);
            await dbContext.SaveChangesAsync(cancellationToken);
        }
        else
        {
            user.Name = name;
            user.Status = "approved";
            user.ApprovedAt ??= now;
            user.BlockedAt = null;
            user.UpdatedAt = now;
        }

        var adminRole = await dbContext.Perans.FirstAsync(x => x.KodePeran == "ADMIN", cancellationToken);
        var pengguna = await dbContext.Penggunas.FirstOrDefaultAsync(x => x.Email == email, cancellationToken);
        if (pengguna is null)
        {
            dbContext.Penggunas.Add(new MPengguna
            {
                Id = Guid.NewGuid(),
                UserId = user.Id,
                IdPeran = adminRole.Id,
                NamaPengguna = name,
                Email = email,
                Password = passwordHash,
                NonAktif = false,
                TglBuat = now
            });
            return;
        }

        pengguna.UserId = user.Id;
        pengguna.IdPeran = adminRole.Id;
        pengguna.NamaPengguna = name;
        pengguna.Password = passwordHash;
        pengguna.NonAktif = false;
        pengguna.TglEdit = now;
    }
}
