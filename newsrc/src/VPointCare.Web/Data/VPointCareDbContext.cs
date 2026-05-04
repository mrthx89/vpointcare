using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data.Entities;

namespace VPointCare.Web.Data;

public class VPointCareDbContext(DbContextOptions<VPointCareDbContext> options) : DbContext(options)
{
    public DbSet<MPeran> Perans => Set<MPeran>();
    public DbSet<MHakAkses> MHakAksesSet => Set<MHakAkses>();
    public DbSet<MPeranHakAkses> MPeranHakAksesSet => Set<MPeranHakAkses>();
    public DbSet<MUser> Users => Set<MUser>();
    public DbSet<MPengguna> Penggunas => Set<MPengguna>();
    public DbSet<MInstansi> MInstansiSet => Set<MInstansi>();
    public DbSet<MCustomer> MCustomerSet => Set<MCustomer>();
    public DbSet<MNomorWhatsapp> MNomorWhatsappSet => Set<MNomorWhatsapp>();
    public DbSet<MGrupWhatsapp> MGrupWhatsappSet => Set<MGrupWhatsapp>();
    public DbSet<MAnggotaGrupWhatsapp> MAnggotaGrupWhatsappSet => Set<MAnggotaGrupWhatsapp>();
    public DbSet<MProdukCustomer> MProdukCustomerSet => Set<MProdukCustomer>();
    public DbSet<MStatusChat> MStatusChatSet => Set<MStatusChat>();
    public DbSet<MKategoriTicket> MKategoriTicketSet => Set<MKategoriTicket>();
    public DbSet<MPrioritasTicket> MPrioritasTicketSet => Set<MPrioritasTicket>();
    public DbSet<MStatusTicket> MStatusTicketSet => Set<MStatusTicket>();
    public DbSet<MSesiWhatsapp> SesiWhatsapps => Set<MSesiWhatsapp>();
    public DbSet<MEndpointIntegrasi> MEndpointIntegrasiSet => Set<MEndpointIntegrasi>();
    public DbSet<MAiProvider> MAiProviderSet => Set<MAiProvider>();
    public DbSet<MHariLibur> MHariLiburSet => Set<MHariLibur>();
    public DbSet<MPengaturanAi> MPengaturanAiSet => Set<MPengaturanAi>();
    public DbSet<MPengetahuan> MPengetahuanSet => Set<MPengetahuan>();
    public DbSet<TLogAktivitas> TLogAktivitasSet => Set<TLogAktivitas>();
    public DbSet<TLogError> TLogErrorSet => Set<TLogError>();
    public DbSet<TLogIntegrasi> LogIntegrasis => Set<TLogIntegrasi>();
    public DbSet<TLogWebhookWaha> LogWebhookWahas => Set<TLogWebhookWaha>();
    public DbSet<TChatM> ChatMasters => Set<TChatM>();
    public DbSet<TChatD> ChatDetails => Set<TChatD>();
    public DbSet<TChatPenugasan> TChatPenugasanSet => Set<TChatPenugasan>();
    public DbSet<TChatCatatanInternal> TChatCatatanInternalSet => Set<TChatCatatanInternal>();
    public DbSet<TTicketM> TTicketMSet => Set<TTicketM>();
    public DbSet<TTicketD> TTicketDSet => Set<TTicketD>();
    public DbSet<TTicketPenugasan> TTicketPenugasanSet => Set<TTicketPenugasan>();
    public DbSet<TTicketLampiran> TTicketLampiranSet => Set<TTicketLampiran>();
    public DbSet<TAiPermintaan> TAiPermintaanSet => Set<TAiPermintaan>();
    public DbSet<TAiRespon> TAiResponSet => Set<TAiRespon>();

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.Entity<MPeran>(entity =>
        {
            entity.ToTable("MPeran");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodePeran).HasColumnName("KodePeran").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaPeran).HasColumnName("NamaPeran").HasColumnType("varchar(100)");
            entity.Property(e => e.Keterangan).HasColumnName("Keterangan").HasColumnType("varchar(255)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MHakAkses>(entity =>
        {
            entity.ToTable("MHakAkses");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeHakAkses).HasColumnName("KodeHakAkses").HasColumnType("varchar(100)");
            entity.Property(e => e.NamaHakAkses).HasColumnName("NamaHakAkses").HasColumnType("varchar(150)");
            entity.Property(e => e.Modul).HasColumnName("Modul").HasColumnType("varchar(100)");
            entity.Property(e => e.Keterangan).HasColumnName("Keterangan").HasColumnType("varchar(255)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MPeranHakAkses>(entity =>
        {
            entity.ToTable("MPeranHakAkses");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdPeran).HasColumnName("IdPeran");
            entity.Property(e => e.IdHakAkses).HasColumnName("IdHakAkses");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MUser>(entity =>
        {
            entity.ToTable("MUser");
            entity.Property(e => e.Id).HasColumnName("id").ValueGeneratedOnAdd();
            entity.Property(e => e.Name).HasColumnName("name").HasColumnType("nvarchar(255)");
            entity.Property(e => e.Email).HasColumnName("email").HasColumnType("nvarchar(255)");
            entity.Property(e => e.EmailVerifiedAt).HasColumnName("email_verified_at");
            entity.Property(e => e.Password).HasColumnName("password").HasColumnType("nvarchar(255)");
            entity.Property(e => e.RememberToken).HasColumnName("remember_token").HasColumnType("nvarchar(100)");
            entity.Property(e => e.Status).HasColumnName("status").HasColumnType("nvarchar(20)");
            entity.Property(e => e.ApprovedAt).HasColumnName("approved_at");
            entity.Property(e => e.BlockedAt).HasColumnName("blocked_at");
            entity.Property(e => e.CreatedAt).HasColumnName("created_at");
            entity.Property(e => e.UpdatedAt).HasColumnName("updated_at");
            entity.HasIndex(e => e.Email).IsUnique();
        });

        modelBuilder.Entity<MPengguna>(entity =>
        {
            entity.ToTable("MPengguna");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.UserId).HasColumnName("UserId");
            entity.Property(e => e.IdPeran).HasColumnName("IdPeran");
            entity.Property(e => e.NamaPengguna).HasColumnName("NamaPengguna").HasColumnType("varchar(150)");
            entity.Property(e => e.Email).HasColumnName("Email").HasColumnType("varchar(150)");
            entity.Property(e => e.Password).HasColumnName("Password").HasColumnType("varchar(255)");
            entity.Property(e => e.NomorWhatsappInternal).HasColumnName("NomorWhatsappInternal").HasColumnType("varchar(30)");
            entity.Property(e => e.FotoProfilPath).HasColumnName("FotoProfilPath").HasColumnType("nvarchar(500)");
            entity.Property(e => e.Jabatan).HasColumnName("Jabatan").HasColumnType("varchar(100)");
            entity.Property(e => e.RememberToken).HasColumnName("RememberToken").HasColumnType("varchar(100)");
            entity.Property(e => e.EmailTerverifikasiPada).HasColumnName("EmailTerverifikasiPada");
            entity.Property(e => e.LoginTerakhirPada).HasColumnName("LoginTerakhirPada");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
            entity.HasIndex(e => e.Email).IsUnique();
        });

        modelBuilder.Entity<MInstansi>(entity =>
        {
            entity.ToTable("MInstansi");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeInstansi).HasColumnName("KodeInstansi").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaInstansi).HasColumnName("NamaInstansi").HasColumnType("varchar(200)");
            entity.Property(e => e.Alamat).HasColumnName("Alamat").HasColumnType("varchar(500)");
            entity.Property(e => e.Kota).HasColumnName("Kota").HasColumnType("varchar(100)");
            entity.Property(e => e.Provinsi).HasColumnName("Provinsi").HasColumnType("varchar(100)");
            entity.Property(e => e.Negara).HasColumnName("Negara").HasColumnType("varchar(100)");
            entity.Property(e => e.KodePos).HasColumnName("KodePos").HasColumnType("varchar(20)");
            entity.Property(e => e.Telepon).HasColumnName("Telepon").HasColumnType("varchar(50)");
            entity.Property(e => e.Email).HasColumnName("Email").HasColumnType("varchar(150)");
            entity.Property(e => e.Website).HasColumnName("Website").HasColumnType("varchar(200)");
            entity.Property(e => e.SumberData).HasColumnName("SumberData").HasColumnType("varchar(50)");
            entity.Property(e => e.IdExternal).HasColumnName("IdExternal").HasColumnType("varchar(100)");
            entity.Property(e => e.TglSinkronTerakhir).HasColumnName("TglSinkronTerakhir");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MCustomer>(entity =>
        {
            entity.ToTable("MCustomer");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdInstansi).HasColumnName("IdInstansi");
            entity.Property(e => e.KodeCustomer).HasColumnName("KodeCustomer").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaCustomer).HasColumnName("NamaCustomer").HasColumnType("varchar(200)");
            entity.Property(e => e.Email).HasColumnName("Email").HasColumnType("varchar(150)");
            entity.Property(e => e.Telepon).HasColumnName("Telepon").HasColumnType("varchar(50)");
            entity.Property(e => e.Jabatan).HasColumnName("Jabatan").HasColumnType("varchar(100)");
            entity.Property(e => e.Catatan).HasColumnName("Catatan").HasColumnType("varchar(1000)");
            entity.Property(e => e.SumberData).HasColumnName("SumberData").HasColumnType("varchar(50)");
            entity.Property(e => e.IdExternal).HasColumnName("IdExternal").HasColumnType("varchar(100)");
            entity.Property(e => e.TglSinkronTerakhir).HasColumnName("TglSinkronTerakhir");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MNomorWhatsapp>(entity =>
        {
            entity.ToTable("MNomorWhatsapp");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdCustomer).HasColumnName("IdCustomer");
            entity.Property(e => e.IdInstansi).HasColumnName("IdInstansi");
            entity.Property(e => e.NomorWhatsapp).HasColumnName("NomorWhatsapp").HasColumnType("varchar(30)");
            entity.Property(e => e.NamaKontak).HasColumnName("NamaKontak").HasColumnType("varchar(150)");
            entity.Property(e => e.JabatanKontak).HasColumnName("JabatanKontak").HasColumnType("varchar(100)");
            entity.Property(e => e.NomorUtama).HasColumnName("NomorUtama");
            entity.Property(e => e.Terverifikasi).HasColumnName("Terverifikasi");
            entity.Property(e => e.SumberData).HasColumnName("SumberData").HasColumnType("varchar(50)");
            entity.Property(e => e.IdExternal).HasColumnName("IdExternal").HasColumnType("varchar(100)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MGrupWhatsapp>(entity =>
        {
            entity.ToTable("MGrupWhatsapp");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdInstansi).HasColumnName("IdInstansi");
            entity.Property(e => e.KodeGrup).HasColumnName("KodeGrup").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaGrup).HasColumnName("NamaGrup").HasColumnType("varchar(200)");
            entity.Property(e => e.IdGrupWaha).HasColumnName("IdGrupWaha").HasColumnType("varchar(200)");
            entity.Property(e => e.NomorGrupWhatsapp).HasColumnName("NomorGrupWhatsapp").HasColumnType("varchar(100)");
            entity.Property(e => e.Deskripsi).HasColumnName("Deskripsi").HasColumnType("varchar(500)");
            entity.Property(e => e.SumberData).HasColumnName("SumberData").HasColumnType("varchar(50)");
            entity.Property(e => e.IdExternal).HasColumnName("IdExternal").HasColumnType("varchar(100)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MAnggotaGrupWhatsapp>(entity =>
        {
            entity.ToTable("MAnggotaGrupWhatsapp");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdGrupWhatsapp).HasColumnName("IdGrupWhatsapp");
            entity.Property(e => e.IdNomorWhatsapp).HasColumnName("IdNomorWhatsapp");
            entity.Property(e => e.IdCustomer).HasColumnName("IdCustomer");
            entity.Property(e => e.PeranAnggota).HasColumnName("PeranAnggota").HasColumnType("varchar(100)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MProdukCustomer>(entity =>
        {
            entity.ToTable("MProdukCustomer");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdCustomer).HasColumnName("IdCustomer");
            entity.Property(e => e.IdInstansi).HasColumnName("IdInstansi");
            entity.Property(e => e.KodeProduk).HasColumnName("KodeProduk").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaProduk).HasColumnName("NamaProduk").HasColumnType("varchar(150)");
            entity.Property(e => e.Keterangan).HasColumnName("Keterangan").HasColumnType("varchar(500)");
            entity.Property(e => e.TglMulai).HasColumnName("TglMulai");
            entity.Property(e => e.TglBerakhir).HasColumnName("TglBerakhir");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MStatusChat>(entity =>
        {
            entity.ToTable("MStatusChat");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeStatusChat).HasColumnName("KodeStatusChat").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaStatusChat).HasColumnName("NamaStatusChat").HasColumnType("varchar(100)");
            entity.Property(e => e.Urutan).HasColumnName("Urutan");
            entity.Property(e => e.Warna).HasColumnName("Warna").HasColumnType("varchar(30)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MKategoriTicket>(entity =>
        {
            entity.ToTable("MKategoriTicket");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeKategori).HasColumnName("KodeKategori").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaKategori).HasColumnName("NamaKategori").HasColumnType("varchar(150)");
            entity.Property(e => e.Keterangan).HasColumnName("Keterangan").HasColumnType("varchar(500)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MPrioritasTicket>(entity =>
        {
            entity.ToTable("MPrioritasTicket");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodePrioritas).HasColumnName("KodePrioritas").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaPrioritas).HasColumnName("NamaPrioritas").HasColumnType("varchar(100)");
            entity.Property(e => e.Urutan).HasColumnName("Urutan");
            entity.Property(e => e.BatasSlaMenit).HasColumnName("BatasSlaMenit");
            entity.Property(e => e.Warna).HasColumnName("Warna").HasColumnType("varchar(30)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MStatusTicket>(entity =>
        {
            entity.ToTable("MStatusTicket");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeStatusTicket).HasColumnName("KodeStatusTicket").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaStatusTicket).HasColumnName("NamaStatusTicket").HasColumnType("varchar(100)");
            entity.Property(e => e.Urutan).HasColumnName("Urutan");
            entity.Property(e => e.StatusFinal).HasColumnName("StatusFinal");
            entity.Property(e => e.Warna).HasColumnName("Warna").HasColumnType("varchar(30)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MSesiWhatsapp>(entity =>
        {
            entity.ToTable("MSesiWhatsapp");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeSesi).HasColumnName("KodeSesi").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaSesi).HasColumnName("NamaSesi").HasColumnType("varchar(150)");
            entity.Property(e => e.BaseUrlWaha).HasColumnName("BaseUrlWaha").HasColumnType("varchar(255)");
            entity.Property(e => e.ApiKey).HasColumnName("ApiKey").HasColumnType("varchar(255)");
            entity.Property(e => e.NomorTerhubung).HasColumnName("NomorTerhubung").HasColumnType("varchar(30)");
            entity.Property(e => e.StatusSesi).HasColumnName("StatusSesi").HasColumnType("varchar(50)");
            entity.Property(e => e.WebhookToken).HasColumnName("WebhookToken").HasColumnType("varchar(255)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
            entity.HasIndex(e => e.KodeSesi).IsUnique();
        });

        modelBuilder.Entity<MEndpointIntegrasi>(entity =>
        {
            entity.ToTable("MEndpointIntegrasi");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeEndpoint).HasColumnName("KodeEndpoint").HasColumnType("varchar(100)");
            entity.Property(e => e.NamaEndpoint).HasColumnName("NamaEndpoint").HasColumnType("varchar(150)");
            entity.Property(e => e.UrlEndpoint).HasColumnName("UrlEndpoint").HasColumnType("varchar(500)");
            entity.Property(e => e.MetodeHttp).HasColumnName("MetodeHttp").HasColumnType("varchar(10)");
            entity.Property(e => e.HeaderJson).HasColumnName("HeaderJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MAiProvider>(entity =>
        {
            entity.ToTable("MAiProvider");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodeProvider).HasColumnName("KodeProvider").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaProvider).HasColumnName("NamaProvider").HasColumnType("varchar(100)");
            entity.Property(e => e.BaseUrl).HasColumnName("BaseUrl").HasColumnType("varchar(255)");
            entity.Property(e => e.ApiKeyTerenkripsi).HasColumnName("ApiKeyTerenkripsi").HasColumnType("varchar(1000)");
            entity.Property(e => e.ModelDefault).HasColumnName("ModelDefault").HasColumnType("varchar(100)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MHariLibur>(entity =>
        {
            entity.ToTable("MHariLibur");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.TanggalLibur).HasColumnName("TanggalLibur");
            entity.Property(e => e.NamaHariLibur).HasColumnName("NamaHariLibur").HasColumnType("varchar(200)");
            entity.Property(e => e.Keterangan).HasColumnName("Keterangan").HasColumnType("varchar(1000)");
            entity.Property(e => e.BerlakuTahunan).HasColumnName("BerlakuTahunan");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MPengaturanAi>(entity =>
        {
            entity.ToTable("MPengaturanAi");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodePengaturan).HasColumnName("KodePengaturan").HasColumnType("varchar(50)");
            entity.Property(e => e.NamaPengaturan).HasColumnName("NamaPengaturan").HasColumnType("varchar(100)");
            entity.Property(e => e.AutoReplyAktif).HasColumnName("AutoReplyAktif");
            entity.Property(e => e.AutoReplyDiluarJamKerja).HasColumnName("AutoReplyDiluarJamKerja");
            entity.Property(e => e.AutoReplyHariLibur).HasColumnName("AutoReplyHariLibur");
            entity.Property(e => e.AutoReplyJamKerjaSapaan).HasColumnName("AutoReplyJamKerjaSapaan");
            entity.Property(e => e.AutoReplyJamKerjaBerlanjut).HasColumnName("AutoReplyJamKerjaBerlanjut");
            entity.Property(e => e.JamKerjaMulai).HasColumnName("JamKerjaMulai").HasColumnType("time(0)");
            entity.Property(e => e.JamKerjaSelesai).HasColumnName("JamKerjaSelesai").HasColumnType("time(0)");
            entity.Property(e => e.HariKerja).HasColumnName("HariKerja").HasColumnType("varchar(50)");
            entity.Property(e => e.ZonaWaktu).HasColumnName("ZonaWaktu").HasColumnType("varchar(100)");
            entity.Property(e => e.ProviderAi).HasColumnName("ProviderAi").HasColumnType("varchar(50)");
            entity.Property(e => e.ModelAi).HasColumnName("ModelAi").HasColumnType("varchar(100)");
            entity.Property(e => e.BaseUrl).HasColumnName("BaseUrl").HasColumnType("varchar(255)");
            entity.Property(e => e.ApiKeyTerenkripsi).HasColumnName("ApiKeyTerenkripsi").HasColumnType("nvarchar(max)");
            entity.Property(e => e.PromptSistem).HasColumnName("PromptSistem").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TemplateDiluarJamKerja).HasColumnName("TemplateDiluarJamKerja").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TemplateHariLibur).HasColumnName("TemplateHariLibur").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TemplateJamKerjaSapaan).HasColumnName("TemplateJamKerjaSapaan").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TemplateFallback).HasColumnName("TemplateFallback").HasColumnType("nvarchar(max)");
            entity.Property(e => e.NotifikasiChatBelumTerbalasAktif).HasColumnName("NotifikasiChatBelumTerbalasAktif");
            entity.Property(e => e.MenitTungguNotifikasi).HasColumnName("MenitTungguNotifikasi");
            entity.Property(e => e.JedaNotifikasiMenit).HasColumnName("JedaNotifikasiMenit");
            entity.Property(e => e.KodePeranPenerimaNotifikasi).HasColumnName("KodePeranPenerimaNotifikasi").HasColumnType("varchar(200)");
            entity.Property(e => e.TemplateNotifikasiChatBelumTerbalas).HasColumnName("TemplateNotifikasiChatBelumTerbalas").HasColumnType("nvarchar(max)");
            entity.Property(e => e.BatasRiwayatPesan).HasColumnName("BatasRiwayatPesan");
            entity.Property(e => e.KirimKeWaha).HasColumnName("KirimKeWaha");
            entity.Property(e => e.ModeKirim).HasColumnName("ModeKirim").HasColumnType("varchar(50)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<MPengetahuan>(entity =>
        {
            entity.ToTable("MPengetahuan");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.KodePengetahuan).HasColumnName("KodePengetahuan").HasColumnType("varchar(50)");
            entity.Property(e => e.JudulPengetahuan).HasColumnName("JudulPengetahuan").HasColumnType("varchar(200)");
            entity.Property(e => e.IsiPengetahuan).HasColumnName("IsiPengetahuan").HasColumnType("nvarchar(max)");
            entity.Property(e => e.Tag).HasColumnName("Tag").HasColumnType("varchar(500)");
            entity.Property(e => e.NonAktif).HasColumnName("NonAktif");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TLogAktivitas>(entity =>
        {
            entity.ToTable("TLogAktivitas");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdPengguna).HasColumnName("IdPengguna");
            entity.Property(e => e.Modul).HasColumnName("Modul").HasColumnType("varchar(100)");
            entity.Property(e => e.Aksi).HasColumnName("Aksi").HasColumnType("varchar(100)");
            entity.Property(e => e.Keterangan).HasColumnName("Keterangan").HasColumnType("varchar(1000)");
            entity.Property(e => e.IpAddress).HasColumnName("IpAddress").HasColumnType("varchar(50)");
            entity.Property(e => e.UserAgent).HasColumnName("UserAgent").HasColumnType("varchar(500)");
            entity.Property(e => e.DataSebelumJson).HasColumnName("DataSebelumJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.DataSesudahJson).HasColumnName("DataSesudahJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglAktivitas).HasColumnName("TglAktivitas");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TLogError>(entity =>
        {
            entity.ToTable("TLogError");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.LevelError).HasColumnName("LevelError").HasColumnType("varchar(50)");
            entity.Property(e => e.PesanError).HasColumnName("PesanError").HasColumnType("nvarchar(max)");
            entity.Property(e => e.FileError).HasColumnName("FileError").HasColumnType("varchar(500)");
            entity.Property(e => e.BarisError).HasColumnName("BarisError");
            entity.Property(e => e.StackTrace).HasColumnName("StackTrace").HasColumnType("nvarchar(max)");
            entity.Property(e => e.ContextJson).HasColumnName("ContextJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglError).HasColumnName("TglError");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TLogIntegrasi>(entity =>
        {
            entity.ToTable("TLogIntegrasi");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdEndpointIntegrasi).HasColumnName("IdEndpointIntegrasi");
            entity.Property(e => e.KodeIntegrasi).HasColumnName("KodeIntegrasi").HasColumnType("varchar(100)");
            entity.Property(e => e.UrlEndpoint).HasColumnName("UrlEndpoint").HasColumnType("varchar(500)");
            entity.Property(e => e.MetodeHttp).HasColumnName("MetodeHttp").HasColumnType("varchar(10)");
            entity.Property(e => e.RequestJson).HasColumnName("RequestJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.ResponseJson).HasColumnName("ResponseJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.StatusHttp).HasColumnName("StatusHttp");
            entity.Property(e => e.Berhasil).HasColumnName("Berhasil");
            entity.Property(e => e.PesanError).HasColumnName("PesanError").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglRequest).HasColumnName("TglRequest");
            entity.Property(e => e.TglResponse).HasColumnName("TglResponse");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TLogWebhookWaha>(entity =>
        {
            entity.ToTable("TLogWebhookWaha");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdSesiWhatsapp).HasColumnName("IdSesiWhatsapp");
            entity.Property(e => e.JenisEvent).HasColumnName("JenisEvent").HasColumnType("varchar(100)");
            entity.Property(e => e.PayloadJson).HasColumnName("PayloadJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglDiterima).HasColumnName("TglDiterima");
            entity.Property(e => e.SudahDiproses).HasColumnName("SudahDiproses");
            entity.Property(e => e.TglDiproses).HasColumnName("TglDiproses");
            entity.Property(e => e.PesanError).HasColumnName("PesanError").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TChatM>(entity =>
        {
            entity.ToTable("TChatM");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdSesiWhatsapp).HasColumnName("IdSesiWhatsapp");
            entity.Property(e => e.IdStatusChat).HasColumnName("IdStatusChat");
            entity.Property(e => e.IdCustomer).HasColumnName("IdCustomer");
            entity.Property(e => e.IdInstansi).HasColumnName("IdInstansi");
            entity.Property(e => e.IdNomorWhatsapp).HasColumnName("IdNomorWhatsapp");
            entity.Property(e => e.IdGrupWhatsapp).HasColumnName("IdGrupWhatsapp");
            entity.Property(e => e.JenisChat).HasColumnName("JenisChat").HasColumnType("varchar(30)");
            entity.Property(e => e.NomorWhatsapp).HasColumnName("NomorWhatsapp").HasColumnType("varchar(30)");
            entity.Property(e => e.NamaKontak).HasColumnName("NamaKontak").HasColumnType("varchar(150)");
            entity.Property(e => e.NamaGrupWhatsapp).HasColumnName("NamaGrupWhatsapp").HasColumnType("varchar(200)");
            entity.Property(e => e.IdWahaTerdeteksi).HasColumnName("IdWahaTerdeteksi").HasColumnType("varchar(200)");
            entity.Property(e => e.NomorWhatsappTerdeteksi).HasColumnName("NomorWhatsappTerdeteksi").HasColumnType("varchar(30)");
            entity.Property(e => e.UrlFotoProfil).HasColumnName("UrlFotoProfil").HasColumnType("nvarchar(1000)");
            entity.Property(e => e.TglFotoProfilDiambil).HasColumnName("TglFotoProfilDiambil");
            entity.Property(e => e.Prioritas).HasColumnName("Prioritas").HasColumnType("varchar(50)");
            entity.Property(e => e.DitugaskanKepada).HasColumnName("DitugaskanKepada");
            entity.Property(e => e.DiambilOleh).HasColumnName("DiambilOleh");
            entity.Property(e => e.TglDiambil).HasColumnName("TglDiambil");
            entity.Property(e => e.TglChatTerakhir).HasColumnName("TglChatTerakhir");
            entity.Property(e => e.TglDibalasTerakhir).HasColumnName("TglDibalasTerakhir");
            entity.Property(e => e.JumlahPesanBelumDibaca).HasColumnName("JumlahPesanBelumDibaca");
            entity.Property(e => e.DitutupOleh).HasColumnName("DitutupOleh");
            entity.Property(e => e.TglDitutup).HasColumnName("TglDitutup");
            entity.Property(e => e.RingkasanAi).HasColumnName("RingkasanAi").HasColumnType("nvarchar(max)");
            entity.Property(e => e.AutoReplyAiAktif).HasColumnName("AutoReplyAiAktif");
            entity.Property(e => e.AiSudahMenyapa).HasColumnName("AiSudahMenyapa");
            entity.Property(e => e.ModeAutoReplyAi).HasColumnName("ModeAutoReplyAi").HasColumnType("varchar(50)");
            entity.Property(e => e.TglAutoReplyAiTerakhir).HasColumnName("TglAutoReplyAiTerakhir");
            entity.Property(e => e.TglNotifikasiBelumTerbalasTerakhir).HasColumnName("TglNotifikasiBelumTerbalasTerakhir");
            entity.Property(e => e.JumlahNotifikasiBelumTerbalas).HasColumnName("JumlahNotifikasiBelumTerbalas");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TChatD>(entity =>
        {
            entity.ToTable("TChatD");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdChatM).HasColumnName("IdChatM");
            entity.Property(e => e.IdLogWebhookWaha).HasColumnName("IdLogWebhookWaha");
            entity.Property(e => e.IdPesanWaha).HasColumnName("IdPesanWaha").HasColumnType("varchar(200)");
            entity.Property(e => e.ArahPesan).HasColumnName("ArahPesan").HasColumnType("varchar(20)");
            entity.Property(e => e.JenisPesan).HasColumnName("JenisPesan").HasColumnType("varchar(50)");
            entity.Property(e => e.IsiPesan).HasColumnName("IsiPesan").HasColumnType("nvarchar(max)");
            entity.Property(e => e.UrlMedia).HasColumnName("UrlMedia").HasColumnType("varchar(1000)");
            entity.Property(e => e.NamaFileMedia).HasColumnName("NamaFileMedia").HasColumnType("varchar(255)");
            entity.Property(e => e.TipeMime).HasColumnName("TipeMime").HasColumnType("varchar(100)");
            entity.Property(e => e.PayloadJson).HasColumnName("PayloadJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.PengirimNomorWhatsapp).HasColumnName("PengirimNomorWhatsapp").HasColumnType("varchar(30)");
            entity.Property(e => e.PengirimNamaKontak).HasColumnName("PengirimNamaKontak").HasColumnType("varchar(150)");
            entity.Property(e => e.DikirimOlehCustomer).HasColumnName("DikirimOlehCustomer");
            entity.Property(e => e.DihasilkanOlehAi).HasColumnName("DihasilkanOlehAi");
            entity.Property(e => e.IdAiRespon).HasColumnName("IdAiRespon");
            entity.Property(e => e.DibalasOleh).HasColumnName("DibalasOleh");
            entity.Property(e => e.TglPesan).HasColumnName("TglPesan");
            entity.Property(e => e.TglDikirim).HasColumnName("TglDikirim");
            entity.Property(e => e.TglDibaca).HasColumnName("TglDibaca");
            entity.Property(e => e.StatusKirim).HasColumnName("StatusKirim").HasColumnType("varchar(50)");
            entity.Property(e => e.PesanError).HasColumnName("PesanError").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
            entity.HasIndex(e => e.IdPesanWaha);
        });

        modelBuilder.Entity<TChatPenugasan>(entity =>
        {
            entity.ToTable("TChatPenugasan");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdChatM).HasColumnName("IdChatM");
            entity.Property(e => e.DitugaskanDari).HasColumnName("DitugaskanDari");
            entity.Property(e => e.DitugaskanKepada).HasColumnName("DitugaskanKepada");
            entity.Property(e => e.AlasanPenugasan).HasColumnName("AlasanPenugasan").HasColumnType("varchar(500)");
            entity.Property(e => e.TglPenugasan).HasColumnName("TglPenugasan");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TChatCatatanInternal>(entity =>
        {
            entity.ToTable("TChatCatatanInternal");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdChatM).HasColumnName("IdChatM");
            entity.Property(e => e.IsiCatatan).HasColumnName("IsiCatatan").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TTicketM>(entity =>
        {
            entity.ToTable("TTicketM");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.NomorTicket).HasColumnName("NomorTicket").HasColumnType("varchar(50)");
            entity.Property(e => e.IdChatM).HasColumnName("IdChatM");
            entity.Property(e => e.IdCustomer).HasColumnName("IdCustomer");
            entity.Property(e => e.IdInstansi).HasColumnName("IdInstansi");
            entity.Property(e => e.IdKategoriTicket).HasColumnName("IdKategoriTicket");
            entity.Property(e => e.IdPrioritasTicket).HasColumnName("IdPrioritasTicket");
            entity.Property(e => e.IdStatusTicket).HasColumnName("IdStatusTicket");
            entity.Property(e => e.JudulTicket).HasColumnName("JudulTicket").HasColumnType("varchar(255)");
            entity.Property(e => e.DeskripsiMasalah).HasColumnName("DeskripsiMasalah").HasColumnType("nvarchar(max)");
            entity.Property(e => e.DibuatDariPesanId).HasColumnName("DibuatDariPesanId");
            entity.Property(e => e.DitugaskanKepada).HasColumnName("DitugaskanKepada");
            entity.Property(e => e.TglDitugaskan).HasColumnName("TglDitugaskan");
            entity.Property(e => e.TglTargetSelesai).HasColumnName("TglTargetSelesai");
            entity.Property(e => e.TglSelesai).HasColumnName("TglSelesai");
            entity.Property(e => e.TglDitutup).HasColumnName("TglDitutup");
            entity.Property(e => e.DitutupOleh).HasColumnName("DitutupOleh");
            entity.Property(e => e.RingkasanAi).HasColumnName("RingkasanAi").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TTicketD>(entity =>
        {
            entity.ToTable("TTicketD");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdTicketM).HasColumnName("IdTicketM");
            entity.Property(e => e.JenisAktivitas).HasColumnName("JenisAktivitas").HasColumnType("varchar(100)");
            entity.Property(e => e.IsiAktivitas).HasColumnName("IsiAktivitas").HasColumnType("nvarchar(max)");
            entity.Property(e => e.StatusSebelum).HasColumnName("StatusSebelum").HasColumnType("varchar(100)");
            entity.Property(e => e.StatusSesudah).HasColumnName("StatusSesudah").HasColumnType("varchar(100)");
            entity.Property(e => e.DitujukanKepada).HasColumnName("DitujukanKepada");
            entity.Property(e => e.TglAktivitas).HasColumnName("TglAktivitas");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TTicketPenugasan>(entity =>
        {
            entity.ToTable("TTicketPenugasan");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdTicketM).HasColumnName("IdTicketM");
            entity.Property(e => e.DitugaskanDari).HasColumnName("DitugaskanDari");
            entity.Property(e => e.DitugaskanKepada).HasColumnName("DitugaskanKepada");
            entity.Property(e => e.AlasanPenugasan).HasColumnName("AlasanPenugasan").HasColumnType("varchar(500)");
            entity.Property(e => e.TglPenugasan).HasColumnName("TglPenugasan");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TTicketLampiran>(entity =>
        {
            entity.ToTable("TTicketLampiran");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdTicketM).HasColumnName("IdTicketM");
            entity.Property(e => e.NamaFile).HasColumnName("NamaFile").HasColumnType("varchar(255)");
            entity.Property(e => e.PathFile).HasColumnName("PathFile").HasColumnType("varchar(1000)");
            entity.Property(e => e.TipeFile).HasColumnName("TipeFile").HasColumnType("varchar(100)");
            entity.Property(e => e.UkuranFile).HasColumnName("UkuranFile");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TAiPermintaan>(entity =>
        {
            entity.ToTable("TAiPermintaan");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdAiProvider).HasColumnName("IdAiProvider");
            entity.Property(e => e.JenisPermintaan).HasColumnName("JenisPermintaan").HasColumnType("varchar(100)");
            entity.Property(e => e.ProviderAi).HasColumnName("ProviderAi").HasColumnType("varchar(50)");
            entity.Property(e => e.ModelAi).HasColumnName("ModelAi").HasColumnType("varchar(100)");
            entity.Property(e => e.IdChatM).HasColumnName("IdChatM");
            entity.Property(e => e.IdTicketM).HasColumnName("IdTicketM");
            entity.Property(e => e.PromptRingkas).HasColumnName("PromptRingkas").HasColumnType("nvarchar(max)");
            entity.Property(e => e.PromptJson).HasColumnName("PromptJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.StatusPermintaan).HasColumnName("StatusPermintaan").HasColumnType("varchar(50)");
            entity.Property(e => e.TglMulai).HasColumnName("TglMulai");
            entity.Property(e => e.TglSelesai).HasColumnName("TglSelesai");
            entity.Property(e => e.PesanError).HasColumnName("PesanError").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });

        modelBuilder.Entity<TAiRespon>(entity =>
        {
            entity.ToTable("TAiRespon");
            entity.Property(e => e.Id).HasColumnName("Id");
            entity.Property(e => e.IdAiPermintaan).HasColumnName("IdAiPermintaan");
            entity.Property(e => e.JenisRespon).HasColumnName("JenisRespon").HasColumnType("varchar(100)");
            entity.Property(e => e.ResponRingkas).HasColumnName("ResponRingkas").HasColumnType("nvarchar(max)");
            entity.Property(e => e.ResponJson).HasColumnName("ResponJson").HasColumnType("nvarchar(max)");
            entity.Property(e => e.TokenInput).HasColumnName("TokenInput");
            entity.Property(e => e.TokenOutput).HasColumnName("TokenOutput");
            entity.Property(e => e.BiayaEstimasi).HasColumnName("BiayaEstimasi").HasColumnType("decimal(18");
            entity.Property(e => e.DisetujuiOleh).HasColumnName("DisetujuiOleh");
            entity.Property(e => e.TglDisetujui).HasColumnName("TglDisetujui");
            entity.Property(e => e.TglBuat).HasColumnName("TglBuat");
            entity.Property(e => e.DibuatOleh).HasColumnName("DibuatOleh");
            entity.Property(e => e.TglEdit).HasColumnName("TglEdit");
            entity.Property(e => e.DieditOleh).HasColumnName("DieditOleh");
        });
    }
}
