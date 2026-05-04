using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MNomorWhatsapp")]
public class MNomorWhatsapp
{
    [Key]
    public Guid Id { get; set; }

    public Guid? IdCustomer { get; set; }

    public Guid? IdInstansi { get; set; }

    [StringLength(30)]
    public string NomorWhatsapp { get; set; } = "";

    [StringLength(150)]
    public string? NamaKontak { get; set; }

    [StringLength(100)]
    public string? JabatanKontak { get; set; }

    public bool NomorUtama { get; set; }

    public bool Terverifikasi { get; set; }

    [StringLength(50)]
    public string? SumberData { get; set; }

    [StringLength(100)]
    public string? IdExternal { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
