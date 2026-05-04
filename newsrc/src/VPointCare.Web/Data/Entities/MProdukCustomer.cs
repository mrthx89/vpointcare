using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MProdukCustomer")]
public class MProdukCustomer
{
    [Key]
    public Guid Id { get; set; }

    public Guid? IdCustomer { get; set; }

    public Guid? IdInstansi { get; set; }

    [StringLength(50)]
    public string KodeProduk { get; set; } = "";

    [StringLength(150)]
    public string NamaProduk { get; set; } = "";

    [StringLength(500)]
    public string? Keterangan { get; set; }

    public DateTime? TglMulai { get; set; }

    public DateTime? TglBerakhir { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
