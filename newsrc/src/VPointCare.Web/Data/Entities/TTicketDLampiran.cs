using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TTicketDLampiran")]
public class TTicketDLampiran
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdTicket { get; set; }

    [StringLength(255)]
    public string NamaFile { get; set; } = "";

    [StringLength(1000)]
    public string PathFile { get; set; } = "";

    [StringLength(100)]
    public string? TipeFile { get; set; }

    public long? UkuranFile { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual TTicket? Ticket { get; set; }
}
